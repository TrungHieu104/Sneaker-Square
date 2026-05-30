@extends('backend.index')

@section('title')
    Thống kê truy cập
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Tổng quát</span></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Người dùng theo trình duyệt</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">
                                STT
                            </th>
                            <th class="text-center">
                                Trình duyệt
                            </th>
                            <th class="text-center">
                                Số lượt xem
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataTopBrowsers as $item)
                            <tr>
                                <td class="text-center">
                                    {{ $loop->iteration }}
                                </td>
                                <td>{{ $item['browser'] }}</td>
                                <td class="text-center">{{ $item['screenPageViews'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->

        <!-- Bordered Table -->
        <div class="card mb-4 card-border-top">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-header">Người dùng theo hệ điều hành</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-bordered w-100">
                        <thead>
                            <tr>
                                <th class="text-center">
                                    STT
                                </th>
                                <th class="text-center">
                                    Hệ điều hành
                                </th>
                                <th class="text-center">
                                    Số lượt xem
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dataSystems as $systems)
                                <tr>
                                    <td class="text-center">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>{{ $systems['operatingSystem'] }}</td>
                                    <td class="text-center">{{ $systems['screenPageViews'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--/ Bordered Table -->

    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Truy cập theo đường dẫn</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">
                                STT
                            </th>
                            <th class="text-center">
                                Đường dẫn
                            </th>
                            <th class="text-center">
                                Số lượt xem
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dataTopReferrers as $itemRefer)
                            <tr>
                                <td class="text-center">
                                    {{ $loop->iteration }}
                                </td>
                                <td>
                                    <a href="{{ $itemRefer['pageReferrer'] }}" target="_blank" class="text-secondary statistical-link">{{ $itemRefer['pageReferrer'] }}</a>
                                </td>
                                <td class="text-center">{{ $itemRefer['screenPageViews'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
