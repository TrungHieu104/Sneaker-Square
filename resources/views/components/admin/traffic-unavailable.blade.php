{{--
    Shown where Google Analytics figures should be when Google could not be
    reached, so an empty chart is not read as "nobody visited".
--}}
<div {{ $attributes->class(['alert alert-warning py-2 px-3']) }} role="alert">
    Chưa lấy được số liệu từ Google Analytics nên phần này đang trống. Kiểm tra
    <code>ANALYTICS_PROPERTY_ID</code> và file key của Google trong <code>storage/app/google/</code>.
</div>
