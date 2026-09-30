@php
    /**
     * Five stars for an average score.
     *
     * The average is rounded to the nearest half, not the nearest whole: 4.7
     * drawn as five full stars reads as a perfect score, which is a claim the
     * shop has not earned. Half a star is the smallest mark a customer can
     * tell apart at this size, so anything finer is a false promise of
     * precision — the number beside the stars carries the rest.
     *
     * `fa-star-half-alt` is the Font Awesome 5 name; the site loads 5.8.2 from
     * the CDN, where the v6 spelling `fa-star-half-stroke` renders nothing.
     */
    $nuaSao = round(((float) ($rating ?? 0)) * 2) / 2;
    $coSuaSize = ! empty($size);
@endphp
<div class="stars text-warning" @if ($coSuaSize) style="font-size: {{ $size }};" @endif>
    @for ($i = 1; $i <= 5; $i++)
        @if ($i <= $nuaSao)
            <i class="fas fa-star"></i>
        @elseif ($i - 0.5 == $nuaSao)
            <i class="fas fa-star-half-alt"></i>
        @else
            <i class="far fa-star"></i>
        @endif
    @endfor
</div>
