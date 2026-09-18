/**
 * The address pickers and the live shipping quote.
 *
 * Every call goes to this application, never to the carrier: GHN's token is a
 * shop-wide credential and this file runs in the customer's browser. The fee
 * shown here is a preview — the number charged is quoted again on the server
 * when the order is written.
 */
(function () {
    'use strict';

    const ROUTES = {
        provinces: '/van-chuyen/tinh-thanh',
        districts: '/van-chuyen/quan-huyen',
        wards: '/van-chuyen/phuong-xa',
        quote: '/van-chuyen/bao-gia',
    };

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function getJson(url, params) {
        const query = new URLSearchParams(params || {}).toString();

        return fetch(query ? url + '?' + query : url, {
            headers: { Accept: 'application/json' },
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        });
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(body),
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) {
                    throw new Error(data.message || 'HTTP ' + response.status);
                }

                return data;
            });
        });
    }

    /**
     * Drops every option but the placeholder the template rendered.
     */
    function reset(select, placeholder) {
        select.innerHTML = '';
        select.appendChild(new Option(placeholder, ''));

        if (select.sync) {
            select.sync();
        }
    }

    function fill(select, rows, valueKey, placeholder) {
        reset(select, placeholder);
        rows.forEach(function (row) {
            select.appendChild(new Option(row.name, row[valueKey]));
        });

        if (select.sync) {
            select.sync();
        }
    }

    /**
     * Turns one <select> into a combobox you can type into.
     *
     * The native <select> stays in the DOM and stays the source of truth:
     * validate_product_checkout.js reads `selectedIndex` and `value` off these
     * elements, and the change events other code listens for still come from
     * the select itself.
     */
    function makeSearchable(select, placeholder) {
        const wrap = document.createElement('div');
        wrap.className = 'addr-pick';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('addr-pick__native');

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-select addr-pick__input';
        input.autocomplete = 'off';
        input.placeholder = placeholder;

        const list = document.createElement('div');
        list.className = 'addr-pick__list';
        list.hidden = true;

        wrap.appendChild(input);
        wrap.appendChild(list);

        let active = -1;

        function render(filter) {
            const needle = fold(filter);
            list.innerHTML = '';
            active = -1;

            // Option 0 is the placeholder the template rendered, never a choice.
            const matches = Array.from(select.options)
                .slice(1)
                .filter(function (option) {
                    return !needle || fold(option.text).includes(needle);
                });

            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'addr-pick__empty';
                empty.textContent = select.options.length > 1
                    ? 'Không tìm thấy'
                    : 'Chưa có dữ liệu';
                list.appendChild(empty);

                return;
            }

            matches.forEach(function (option) {
                const row = document.createElement('div');
                row.className = 'addr-pick__row';
                row.textContent = option.text;
                row.dataset.value = option.value;
                row.addEventListener('mousedown', function (event) {
                    // mousedown, not click: blur would close the list first.
                    event.preventDefault();
                    choose(option.value);
                });
                list.appendChild(row);
            });
        }

        function choose(value) {
            select.value = value;
            select.dispatchEvent(new Event('change'));
            close();
        }

        function open() {
            render(input.value === selectedText() ? '' : input.value);
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            list.hidden = true;
            input.removeAttribute('aria-expanded');
            sync();
        }

        function selectedText() {
            const option = select.options[select.selectedIndex];

            return option && option.value ? option.text : '';
        }

        function sync() {
            input.value = selectedText();
        }

        function move(step) {
            const rows = list.querySelectorAll('.addr-pick__row');

            if (!rows.length) {
                return;
            }

            active = (active + step + rows.length) % rows.length;
            rows.forEach(function (row, index) {
                row.classList.toggle('is-active', index === active);
            });
            rows[active].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('focus', open);
        input.addEventListener('input', function () {
            list.hidden = false;
            render(input.value);
        });
        input.addEventListener('blur', function () {
            setTimeout(close, 120);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.hidden) {
                    open();
                }
                move(event.key === 'ArrowDown' ? 1 : -1);
            } else if (event.key === 'Enter') {
                const row = list.querySelectorAll('.addr-pick__row')[active];
                if (row) {
                    event.preventDefault();
                    choose(row.dataset.value);
                }
            } else if (event.key === 'Escape') {
                close();
                input.blur();
            }
        });

        // Whenever something else refills or resets the select.
        select.addEventListener('change', sync);
        select.sync = sync;

        sync();
    }

    /**
     * Lowercase and strip tone marks, so "ho chi minh" finds "Hồ Chí Minh".
     */
    function fold(text) {
        return (text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .trim();
    }

    function injectStyles() {
        if (document.getElementById('addr-pick-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'addr-pick-styles';
        style.textContent = [
            '.addr-pick{position:relative}',
            '.addr-pick__native{position:absolute;opacity:0;pointer-events:none;height:0;width:0}',
            '.addr-pick__input{background-image:none;cursor:text}',
            '.addr-pick__list{position:absolute;z-index:1080;left:0;right:0;top:calc(100% + 2px);',
            'max-height:240px;overflow-y:auto;background:#fff;border:1px solid #dee2e6;',
            'border-radius:.375rem;box-shadow:0 .5rem 1rem rgba(0,0,0,.12)}',
            '.addr-pick__row{padding:.45rem .75rem;cursor:pointer;font-size:.95rem}',
            '.addr-pick__row:hover,.addr-pick__row.is-active{background:#fff1e6;color:#ff6b00}',
            '.addr-pick__empty{padding:.55rem .75rem;color:#8a8a8a;font-style:italic;font-size:.9rem}',
        ].join('');
        document.head.appendChild(style);
    }

    function setUpForm(form, provinces) {
        const province = form.querySelector('.province-select');
        const district = form.querySelector('.district-select');
        const ward = form.querySelector('.ward-select');

        if (!province || !district || !ward) {
            return;
        }

        // The names go to the order, the ids go to the carrier. Both are needed:
        // a district id means nothing on a printed label, and a district name
        // means nothing to GHN.
        const provinceName = form.querySelector('#info_province');
        const districtName = form.querySelector('#info_district');
        const wardName = form.querySelector('#info_ward');
        const districtId = form.querySelector('.district-id-field');
        const wardCode = form.querySelector('.ward-code-field');

        const quoteBox = form.querySelector('.shipping-quote');
        const quoteFee = form.querySelector('.shipping-quote-fee');
        const quoteEta = form.querySelector('.shipping-quote-eta');

        function selectedText(select) {
            const option = select.options[select.selectedIndex];

            return option && option.value ? option.text : '';
        }

        function showQuote(text, eta) {
            if (quoteFee) {
                quoteFee.textContent = text;
            }
            if (quoteEta) {
                quoteEta.textContent = eta || '';
            }
        }

        function clearQuote(message) {
            if (wardCode) {
                wardCode.value = '';
            }
            showQuote(message, '');
        }

        function refreshQuote() {
            if (!districtId || !districtId.value || !wardCode || !wardCode.value) {
                return;
            }

            showQuote('Đang tính phí…', '');

            postJson(ROUTES.quote, {
                district_id: districtId.value,
                ward_code: wardCode.value,
            })
                .then(function (quote) {
                    showQuote(
                        quote.fee_text,
                        quote.estimated_text ? 'Dự kiến nhận hàng: ' + quote.estimated_text : ''
                    );
                    if (quoteBox) {
                        quoteBox.dataset.fee = quote.fee;
                    }
                })
                .catch(function (error) {
                    showQuote('Chưa tính được phí', error.message);
                });
        }

        fill(province, provinces, 'id', 'Chọn tỉnh / thành phố');

        injectStyles();
        makeSearchable(province, 'Gõ để tìm tỉnh / thành phố');
        makeSearchable(district, 'Gõ để tìm quận / huyện');
        makeSearchable(ward, 'Gõ để tìm phường / xã');

        province.addEventListener('change', function () {
            if (provinceName) {
                provinceName.value = selectedText(province);
            }

            reset(district, 'Chọn Quận / Huyện');
            reset(ward, 'Chọn Phường / Xã');
            if (districtId) {
                districtId.value = '';
            }
            clearQuote('Chọn phường / xã để xem phí');

            if (!province.value) {
                return;
            }

            getJson(ROUTES.districts, { province_id: province.value })
                .then(function (data) {
                    fill(district, data.districts, 'id', 'Chọn Quận / Huyện');
                })
                .catch(function () {
                    clearQuote('Không tải được danh sách quận / huyện');
                });
        });

        district.addEventListener('change', function () {
            if (districtName) {
                districtName.value = selectedText(district);
            }
            if (districtId) {
                districtId.value = district.value;
            }

            reset(ward, 'Chọn Phường / Xã');
            clearQuote('Chọn phường / xã để xem phí');

            if (!district.value) {
                return;
            }

            getJson(ROUTES.wards, { district_id: district.value })
                .then(function (data) {
                    fill(ward, data.wards, 'code', 'Chọn Phường / Xã');
                })
                .catch(function () {
                    clearQuote('Không tải được danh sách phường / xã');
                });
        });

        ward.addEventListener('change', function () {
            if (wardName) {
                wardName.value = selectedText(ward);
            }
            if (wardCode) {
                wardCode.value = ward.value;
            }

            if (!ward.value) {
                clearQuote('Chọn phường / xã để xem phí');

                return;
            }

            refreshQuote();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const forms = Array.from(document.querySelectorAll('.province-select'))
            .map(function (select) {
                return select.closest('form');
            })
            .filter(function (form, index, all) {
                return form && all.indexOf(form) === index;
            });

        if (!forms.length) {
            return;
        }

        // One list for every form on the page: the page holds one per address.
        getJson(ROUTES.provinces)
            .then(function (data) {
                forms.forEach(function (form) {
                    setUpForm(form, data.provinces);
                });
            })
            .catch(function () {
                forms.forEach(function (form) {
                    const fee = form.querySelector('.shipping-quote-fee');
                    if (fee) {
                        fee.textContent = 'Không tải được danh sách địa chỉ';
                    }
                });
            });
    });
})();
