/**
 * Measures a foot standing on an A4 sheet, entirely in the browser.
 *
 * The sheet is the ruler: its size is fixed at 210 × 297 mm, so once its four
 * corners are found the photo can be warped flat onto a grid where every
 * pixel is a known fraction of a millimetre. The foot is then whatever is
 * darker than the paper, and its length is read straight off that grid.
 */
(function () {
    'use strict';

    const PAPER_W_MM = 210;
    const PAPER_H_MM = 297;
    const PX_PER_MM = 4;
    const MAX_SIDE = 1280;
    const MIN_MM = 180;
    const MAX_MM = 330;
    const A4_RATIO = PAPER_H_MM / PAPER_W_MM;
    const TOE_BAND_MM = 60;
    const TINT_SPREAD = 4;
    const MIN_UNIFORM = 0.85;
    const MIN_TINT_GAP = 4;
    const MIN_LIGHT_GAP = 40;
    const NO_PAPER = 'Không tìm thấy tờ giấy A4 trong ảnh. Hãy chụp lại sao cho thấy đủ 4 góc giấy, nền sàn tối hơn tờ giấy.';
    const HEIF_BRANDS = ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'mif1', 'msf1'];

    let loading = null;
    let heicLoading = null;

    function fail(message) {
        const error = new Error(message);
        error.isMeasureError = true;
        return error;
    }

    /**
     * Resolves to `{ cv }` rather than to cv itself: the emscripten module is a
     * thenable, and resolving a promise with it makes the promise wait on it
     * forever.
     */
    function ready(url) {
        if (loading) {
            return loading;
        }

        loading = new Promise(function (resolve, reject) {
            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = function () {
                const settle = function (cv) {
                    window.cv = cv;
                    resolve({ cv: cv });
                };
                const loaded = window.cv;

                if (loaded && loaded.Mat) {
                    settle(loaded);
                } else if (loaded && typeof loaded.then === 'function') {
                    loaded.then(settle);
                } else if (loaded) {
                    loaded.onRuntimeInitialized = function () { settle(loaded); };
                } else {
                    reject(fail('Không tải được bộ xử lý ảnh, bạn thử tải lại trang.'));
                }
            };
            script.onerror = function () {
                loading = null;
                reject(fail('Không tải được bộ xử lý ảnh, bạn thử tải lại trang.'));
            };
            document.head.appendChild(script);
        });

        return loading;
    }

    function loadScript(url, global) {
        return new Promise(function (resolve, reject) {
            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = function () {
                window[global] ? resolve(window[global]) : reject(new Error(global + ' missing'));
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Read from the file's first bytes, not its name: photos copied off an
     * iPhone are often HEIC under a .jpg or .png name.
     */
    async function isHeif(file) {
        const head = new Uint8Array(await file.slice(0, 12).arrayBuffer());
        const text = String.fromCharCode.apply(null, head);

        return text.slice(4, 8) === 'ftyp' && HEIF_BRANDS.indexOf(text.slice(8, 12)) !== -1;
    }

    function decode(blob) {
        return new Promise(function (resolve, reject) {
            const image = new Image();
            const url = URL.createObjectURL(blob);
            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };
            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(fail('Không mở được ảnh này, bạn thử chọn ảnh khác.'));
            };
            image.src = url;
        });
    }

    /**
     * Opens the chosen photo as an image. Only Safari reads HEIC itself, so
     * for every other browser the photo is converted first, with a decoder
     * that is fetched the first time one is needed.
     *
     * @param {File} file
     * @param {string} heicUrl  where the HEIC decoder can be loaded from
     */
    async function open(file, heicUrl) {
        if (!(await isHeif(file))) {
            return decode(file);
        }

        try {
            return await decode(file);
        } catch {
            heicLoading = heicLoading || loadScript(heicUrl, 'HeicTo').catch(function (error) {
                heicLoading = null;
                throw error;
            });

            try {
                const HeicTo = await heicLoading;
                return await decode(await HeicTo({ blob: file, type: 'image/jpeg', quality: 0.92 }));
            } catch {
                throw fail('Không đọc được ảnh HEIC này. Bạn thử chụp lại, hoặc chọn ảnh JPG.');
            }
        }
    }

    function drawScaled(image) {
        const scale = Math.min(1, MAX_SIDE / Math.max(image.width, image.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.width * scale);
        canvas.height = Math.round(image.height * scale);
        canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

        return canvas;
    }

    function largestContour(cv, binary) {
        const contours = new cv.MatVector();
        const hierarchy = new cv.Mat();
        cv.findContours(binary, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        let best = null;
        let bestArea = 0;

        for (let i = 0; i < contours.size(); i++) {
            const contour = contours.get(i);
            const area = cv.contourArea(contour);

            if (area > bestArea) {
                if (best) {
                    best.delete();
                }
                best = contour;
                bestArea = area;
            } else {
                contour.delete();
            }
        }

        contours.delete();
        hierarchy.delete();

        return { contour: best, area: bestArea };
    }

    /**
     * The hull rather than the contour itself: the leg standing on the sheet
     * bites a notch out of its outline, but never out of its corners.
     */
    function quadFrom(cv, contour) {
        const hull = new cv.Mat();
        cv.convexHull(contour, hull, false, true);
        const perimeter = cv.arcLength(hull, true);
        let corners = null;
        let chamfered = null;

        for (let eps = 0.01; eps <= 0.1 && !corners; eps += 0.01) {
            const approx = new cv.Mat();
            cv.approxPolyDP(hull, approx, eps * perimeter, true);
            const points = [];
            for (let i = 0; i < approx.rows; i++) {
                points.push({ x: approx.data32S[i * 2], y: approx.data32S[i * 2 + 1] });
            }
            approx.delete();

            if (points.length === 4) {
                corners = points;
            } else if (points.length === 5 && !chamfered) {
                chamfered = restoreCorner(points);
            }
        }

        hull.delete();

        return corners || chamfered;
    }

    /**
     * A leg covering one corner of the sheet cuts it off, leaving five sides
     * where there should be four. The shortest side is the cut: dropping it
     * and extending its two neighbours until they meet puts the corner back.
     */
    function restoreCorner(points) {
        const n = points.length;
        let cut = 0;
        let shortest = Infinity;
        for (let i = 0; i < n; i++) {
            const a = points[i];
            const b = points[(i + 1) % n];
            const length = Math.hypot(a.x - b.x, a.y - b.y);
            if (length < shortest) {
                shortest = length;
                cut = i;
            }
        }

        const before = [points[(cut + n - 1) % n], points[cut]];
        const after = [points[(cut + 1) % n], points[(cut + 2) % n]];
        const corner = intersect(before[0], before[1], after[0], after[1]);
        if (!corner) {
            return null;
        }

        const quad = [];
        for (let i = 0; i < n; i++) {
            if (i === cut) {
                quad.push(corner);
            } else if (i !== (cut + 1) % n) {
                quad.push(points[i]);
            }
        }

        return quad;
    }

    function intersect(p1, p2, p3, p4) {
        const d = (p1.x - p2.x) * (p3.y - p4.y) - (p1.y - p2.y) * (p3.x - p4.x);
        if (Math.abs(d) < 1e-6) {
            return null;
        }
        const a = p1.x * p2.y - p1.y * p2.x;
        const b = p3.x * p4.y - p3.y * p4.x;

        return {
            x: Math.round((a * (p3.x - p4.x) - (p1.x - p2.x) * b) / d),
            y: Math.round((a * (p3.y - p4.y) - (p1.y - p2.y) * b) / d),
        };
    }

    /**
     * Every outline big enough to be the sheet, largest first.
     */
    function candidateQuads(cv, binary, imageArea) {
        const contours = new cv.MatVector();
        const hierarchy = new cv.Mat();
        cv.findContours(binary, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);

        const sized = [];
        for (let i = 0; i < contours.size(); i++) {
            const contour = contours.get(i);
            const area = cv.contourArea(contour) / imageArea;
            contour.delete();
            if (area > 0.04 && area < 0.97) {
                sized.push({ index: i, area: area });
            }
        }
        sized.sort(function (a, b) { return b.area - a.area; });

        let corners = null;
        for (const candidate of sized) {
            const contour = contours.get(candidate.index);
            corners = quadFrom(cv, contour);
            contour.delete();
            if (corners && looksLikeA4(corners)) {
                break;
            }
            corners = null;
        }

        contours.delete();
        hierarchy.delete();

        return corners;
    }

    /**
     * The brightness a share of the brightest pixels reaches: the sheet is
     * the whitest thing in the photo, even when the floor is pale too.
     */
    function brightLevel(gray, share) {
        const counts = new Uint32Array(256);
        const data = gray.data;
        for (let i = 0; i < data.length; i++) {
            counts[data[i]]++;
        }

        let seen = 0;
        for (let level = 255; level > 0; level--) {
            seen += counts[level];
            if (seen >= data.length * share) {
                return level;
            }
        }

        return 0;
    }

    /**
     * Marks the bluest share of the photo. Office paper is whitened with a
     * blue tint, so on a pale floor of the same brightness it is still the
     * coolest thing in the frame, while skin and tiles lean yellow.
     */
    function coolestShare(cv, src, out, share) {
        const lab = lightAndTint(cv, src);
        const counts = new Uint32Array(256);
        const data = lab.tint.data;
        for (let i = 0; i < data.length; i++) {
            counts[data[i]]++;
        }
        let level = 0;
        for (let seen = 0; level < 255; level++) {
            seen += counts[level];
            if (seen >= data.length * share) {
                break;
            }
        }

        cv.threshold(lab.tint, out, level + 3, 255, cv.THRESH_BINARY_INV);

        lab.light.delete();
        lab.tint.delete();
    }

    /**
     * Four ways of telling the sheet from the floor, tried in turn: its blue
     * tint, a plain threshold for a dark floor, the near-white band for a
     * pale one, and edges when none of those separates them.
     */
    function findPaper(cv, cool, gray, imageArea) {
        const attempts = [
            function (out) {
                cool.copyTo(out);
            },
            function (out) {
                cv.threshold(gray, out, 0, 255, cv.THRESH_BINARY + cv.THRESH_OTSU);
            },
            function (out) {
                cv.threshold(gray, out, brightLevel(gray, 0.1) - 12, 255, cv.THRESH_BINARY);
            },
            function (out) {
                cv.Canny(gray, out, 30, 90);
                const kernel = cv.Mat.ones(5, 5, cv.CV_8U);
                cv.dilate(out, out, kernel);
                kernel.delete();
            },
        ];

        for (const attempt of attempts) {
            const binary = new cv.Mat();
            attempt(binary);
            const kernel = cv.Mat.ones(9, 9, cv.CV_8U);
            cv.morphologyEx(binary, binary, cv.MORPH_CLOSE, kernel);
            kernel.delete();

            const corners = candidateQuads(cv, binary, imageArea);
            binary.delete();

            if (corners) {
                return corners;
            }
        }

        throw fail(NO_PAPER);
    }

    /**
     * Any bright four-sided patch of floor is a candidate for the sheet, so
     * only one shaped like A4 is believed: long sides about 1.41 times the
     * short ones, and corners not much further from square than a phone held
     * at an angle makes them.
     */
    function looksLikeA4(corners) {
        const [tl, tr, br, bl] = orderCorners(corners);
        const distance = function (p, q) { return Math.hypot(p.x - q.x, p.y - q.y); };
        const ratio = (distance(tl, bl) + distance(tr, br)) / (distance(tl, tr) + distance(bl, br));

        if (Math.abs(ratio - A4_RATIO) > 0.4) {
            return false;
        }

        const ring = [tl, tr, br, bl];
        for (let i = 0; i < 4; i++) {
            const at = ring[i];
            const prev = ring[(i + 3) % 4];
            const next = ring[(i + 1) % 4];
            const cos = ((prev.x - at.x) * (next.x - at.x) + (prev.y - at.y) * (next.y - at.y))
                / (distance(prev, at) * distance(next, at));

            if (Math.abs(cos) > Math.cos(55 * Math.PI / 180)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Orders the corners so the sheet always comes out portrait, whichever
     * way the phone was held.
     */
    function orderCorners(corners) {
        const bySum = corners.slice().sort(function (a, b) { return (a.x + a.y) - (b.x + b.y); });
        const byDiff = corners.slice().sort(function (a, b) { return (a.x - a.y) - (b.x - b.y); });
        const tl = bySum[0];
        const br = bySum[3];
        const tr = byDiff[3];
        const bl = byDiff[0];
        const distance = function (p, q) { return Math.hypot(p.x - q.x, p.y - q.y); };

        const landscape = distance(tl, tr) + distance(bl, br) > distance(tl, bl) + distance(tr, br);

        return landscape ? [tr, br, bl, tl] : [tl, tr, br, bl];
    }

    function warpPaper(cv, src, corners) {
        const width = PAPER_W_MM * PX_PER_MM;
        const height = PAPER_H_MM * PX_PER_MM;
        const from = cv.matFromArray(4, 1, cv.CV_32FC2, corners.flatMap(function (p) { return [p.x, p.y]; }));
        const to = cv.matFromArray(4, 1, cv.CV_32FC2, [0, 0, width, 0, width, height, 0, height]);
        const matrix = cv.getPerspectiveTransform(from, to);
        const flat = new cv.Mat();
        cv.warpPerspective(src, flat, matrix, new cv.Size(width, height), cv.INTER_LINEAR, cv.BORDER_REPLICATE);

        from.delete();
        to.delete();
        matrix.delete();

        return flat;
    }

    /**
     * The heel is pressed against one short edge, so the length is measured
     * from that edge to the tip of the toes. The few millimetres along every
     * edge are dropped first: that is where a slightly-off corner lets the
     * floor show through.
     */
    function measureFoot(cv, flat) {
        const width = flat.cols;
        const height = flat.rows;
        const margin = 3 * PX_PER_MM;

        const gray = new cv.Mat();
        cv.cvtColor(flat, gray, cv.COLOR_RGBA2GRAY);
        cv.GaussianBlur(gray, gray, new cv.Size(7, 7), 0);

        const binary = new cv.Mat();
        cv.threshold(gray, binary, 0, 255, cv.THRESH_BINARY_INV + cv.THRESH_OTSU);
        gray.delete();

        const kernel = cv.Mat.ones(7, 7, cv.CV_8U);
        cv.morphologyEx(binary, binary, cv.MORPH_OPEN, kernel);
        cv.morphologyEx(binary, binary, cv.MORPH_CLOSE, kernel);
        kernel.delete();

        cv.rectangle(binary, new cv.Point(0, 0), new cv.Point(width - 1, height - 1), new cv.Scalar(0), margin * 2);

        const sheet = sheetColour(cv, flat, binary, margin);
        const found = largestContour(cv, binary);
        binary.delete();

        if (!found.contour || found.area < width * height * 0.08) {
            if (found.contour) {
                found.contour.delete();
            }
            throw fail('Không thấy bàn chân trên tờ giấy. Hãy đi chân trần hoặc mang tất màu tối, và chụp ở nơi đủ sáng.');
        }

        const box = cv.boundingRect(found.contour);
        const touchesTop = box.y <= margin + 3;
        const touchesBottom = box.y + box.height >= height - margin - 3;

        if (touchesTop && touchesBottom) {
            found.contour.delete();
            throw fail('Bàn chân hoặc bóng đổ chạm cả hai đầu tờ giấy. Hãy để gót sát một mép ngắn và chụp ở nơi không có bóng.');
        }

        let heelY;
        let toeY;

        if (touchesTop) {
            heelY = 0;
            toeY = box.y + box.height;
        } else if (touchesBottom) {
            heelY = height;
            toeY = box.y;
        } else {
            heelY = box.y + box.height;
            toeY = box.y;
        }

        const toes = toeBand(cv, found.contour, width, height, toeY, toeY < heelY);
        found.contour.delete();

        // Standing on the sheet leaves paper either side of the toes. Toes
        // that reach a long edge mean the foot is beside the sheet, or that
        // the sheet found is really a patch of floor. Only the toes are
        // checked: the leg of someone photographing their own foot crosses
        // the sheet, and that is fine.
        if (toes.x <= margin + 3 || toes.x + toes.width >= width - margin - 3) {
            throw fail('Bàn chân phải đứng trên tờ giấy, hai bên bàn chân còn thấy giấy. Hãy chụp lại theo hướng dẫn.');
        }

        return {
            sheet: sheet,
            lengthMm: Math.round(Math.abs(heelY - toeY) / PX_PER_MM),
            heelY: heelY,
            toeY: toeY,
            box: box,
        };
    }

    /**
     * The left and right reach of the foot across the first few centimetres
     * behind the tip of the toes.
     */
    function toeBand(cv, contour, width, height, toeY, toesUp) {
        const band = TOE_BAND_MM * PX_PER_MM;
        const top = Math.max(0, toesUp ? toeY : toeY - band);
        const rows = Math.min(band, height - top);
        const mask = cv.Mat.zeros(height, width, cv.CV_8U);
        const outline = new cv.MatVector();
        outline.push_back(contour);
        cv.drawContours(mask, outline, 0, new cv.Scalar(255), cv.FILLED);

        let left = width;
        let right = -1;
        const data = mask.data;
        for (let y = top; y < top + rows; y++) {
            const row = y * width;
            for (let x = 0; x < width; x++) {
                if (data[row + x]) {
                    left = Math.min(left, x);
                    right = Math.max(right, x);
                }
            }
        }

        outline.delete();
        mask.delete();

        return { x: left, width: right - left + 1 };
    }

    /**
     * Lightness and the blue-yellow axis of a photo, from CIE Lab.
     */
    function lightAndTint(cv, rgba) {
        const rgb = new cv.Mat();
        const lab = new cv.Mat();
        const channels = new cv.MatVector();
        cv.cvtColor(rgba, rgb, cv.COLOR_RGBA2RGB);
        cv.cvtColor(rgb, lab, cv.COLOR_RGB2Lab);
        cv.split(lab, channels);
        const light = channels.get(0);
        const tint = channels.get(2);
        channels.delete();
        lab.delete();
        rgb.delete();

        return { light: light, tint: tint };
    }

    function median(counts, total) {
        let level = 0;
        for (let seen = 0; level < 255; level++) {
            seen += counts[level];
            if (seen >= total / 2) {
                break;
            }
        }

        return level;
    }

    /**
     * The colour of the sheet around the foot, and how much of it shares one
     * tint. Paper is a single colour however the light falls on it; a patch
     * of floor mistaken for the sheet holds tile and paper side by side.
     */
    function sheetColour(cv, flat, foot, margin) {
        const lab = lightAndTint(cv, flat);
        const width = foot.cols;
        const height = foot.rows;
        const lights = new Uint32Array(256);
        const tints = new Uint32Array(256);
        let around = 0;
        for (let y = margin; y < height - margin; y += 2) {
            for (let x = margin; x < width - margin; x += 2) {
                const i = y * width + x;
                if (!foot.data[i]) {
                    lights[lab.light.data[i]]++;
                    tints[lab.tint.data[i]]++;
                    around++;
                }
            }
        }
        lab.light.delete();
        lab.tint.delete();

        const tint = median(tints, around);
        let near = 0;
        for (let level = Math.max(0, tint - TINT_SPREAD); level <= Math.min(255, tint + TINT_SPREAD); level++) {
            near += tints[level];
        }

        return { light: median(lights, around), tint: tint, uniform: around ? near / around : 0 };
    }

    /**
     * The colour of the floor in a band just outside the sheet.
     */
    function floorColour(cv, src, corners) {
        const sheet = cv.Mat.zeros(src.rows, src.cols, cv.CV_8U);
        const outline = cv.matFromArray(4, 1, cv.CV_32SC2, corners.flatMap(function (p) { return [p.x, p.y]; }));
        const polygons = new cv.MatVector();
        polygons.push_back(outline);
        cv.fillPoly(sheet, polygons, new cv.Scalar(255));

        const reach = Math.max(9, Math.round(Math.max(src.rows, src.cols) * 0.03));
        const grown = new cv.Mat();
        const kernel = cv.Mat.ones(reach, reach, cv.CV_8U);
        cv.dilate(sheet, grown, kernel);

        const lab = lightAndTint(cv, src);
        const lights = new Uint32Array(256);
        const tints = new Uint32Array(256);
        let ring = 0;
        for (let i = 0; i < sheet.data.length; i += 2) {
            if (grown.data[i] && !sheet.data[i]) {
                lights[lab.light.data[i]]++;
                tints[lab.tint.data[i]]++;
                ring++;
            }
        }

        lab.light.delete();
        lab.tint.delete();
        kernel.delete();
        grown.delete();
        polygons.delete();
        outline.delete();
        sheet.delete();

        return { light: median(lights, ring), tint: median(tints, ring) };
    }

    function drawResult(cv, flat, foot, canvas) {
        const green = new cv.Scalar(25, 135, 84, 255);
        const x = Math.round(foot.box.x + foot.box.width / 2);

        cv.rectangle(flat, new cv.Point(foot.box.x, foot.box.y),
            new cv.Point(foot.box.x + foot.box.width, foot.box.y + foot.box.height), green, 3);
        cv.line(flat, new cv.Point(x, foot.heelY), new cv.Point(x, foot.toeY), green, 6);
        cv.line(flat, new cv.Point(x - 40, foot.toeY), new cv.Point(x + 40, foot.toeY), green, 6);
        cv.line(flat, new cv.Point(x - 40, foot.heelY), new cv.Point(x + 40, foot.heelY), green, 6);
        cv.imshow(canvas, flat);
    }

    /**
     * @param {HTMLImageElement|HTMLCanvasElement} image
     * @param {HTMLCanvasElement} preview  receives the flattened sheet with the measurement drawn on
     */
    async function measure(opencvUrl, image, preview) {
        const cv = (await ready(opencvUrl)).cv;
        const canvas = drawScaled(image);
        const src = cv.imread(canvas);
        const gray = new cv.Mat();
        const cool = new cv.Mat();
        let flat = null;

        try {
            cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
            cv.GaussianBlur(gray, gray, new cv.Size(5, 5), 0);
            coolestShare(cv, src, cool, 0.05);

            const corners = orderCorners(findPaper(cv, cool, gray, canvas.width * canvas.height));
            flat = warpPaper(cv, src, corners);
            const foot = measureFoot(cv, flat);
            const floor = floorColour(cv, src, corners);

            // What was taken for the sheet must look like one: a single colour,
            // and one that stands out from the floor around it. Otherwise it is
            // a tile, and measuring against it would suggest a wrong size.
            const standsOut = Math.abs(foot.sheet.tint - floor.tint) >= MIN_TINT_GAP
                || Math.abs(foot.sheet.light - floor.light) >= MIN_LIGHT_GAP;
            if (foot.sheet.uniform < MIN_UNIFORM || !standsOut) {
                throw fail(NO_PAPER);
            }

            if (foot.lengthMm < MIN_MM || foot.lengthMm > MAX_MM) {
                throw fail('Kết quả đo (' + foot.lengthMm + ' mm) không hợp lý. Hãy chụp lại theo hướng dẫn, hoặc tự đo bằng thước.');
            }

            if (preview) {
                drawResult(cv, flat, foot, preview);
            }

            return { lengthMm: foot.lengthMm };
        } finally {
            src.delete();
            gray.delete();
            cool.delete();
            if (flat) {
                flat.delete();
            }
        }
    }

    window.FootMeasure = { ready: ready, open: open, measure: measure };
})();
