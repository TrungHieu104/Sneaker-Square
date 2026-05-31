$(document).ready(function() {
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }

    });

    $.ajax({
        url: "/token-delivery",
        method: 'POST',
        success: function(response) {

            var tokenAPI = response.tokenAPI;
            var shopID = response.shopID;
            
            const urlProvince = "https://online-gateway.ghn.vn/shiip/public-api/master-data/province";
            const urlDistrict = "https://online-gateway.ghn.vn/shiip/public-api/master-data/district";
            const urlWard = "https://online-gateway.ghn.vn/shiip/public-api/master-data/ward";
            const urlService = "https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/available-services";
            const urlFee = "https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee";
            
            $.ajax({

                url: urlProvince,
                method: 'GET',
                headers: {
                    token: tokenAPI,
                },
                success: function(response) {
                    var province = response.data;
                    const provinceData = document.querySelectorAll(".province-data");

                    if(province != '') {
                        $.each(province, function (key, pro) {
                            $(provinceData).append(new Option(pro.ProvinceName, pro.ProvinceID));
                        });
                    }
                }

            });
        
            $(document).on('change', '#province', function() {

                var provinceID = $(this).val(); 
                var provinceName = $(this).find('option:selected').text();
                $('.my-data-province').find('#info_province').val(provinceName);

                $.ajax({

                    url: urlDistrict,
                    method: 'GET',
                    headers: {
                        token: tokenAPI,
                    },
                    data: {
                        province_id: provinceID,
                    },
                    success: function(response) {
                        var districts = response.data;
                        const districtData = document.querySelectorAll(".district-data");
                        $(districtData).find('option:not(:first)').remove();

                        if(provinceID != '') {
                            $.each(districts, function(key, dis) {
                                $(districtData).append(`<option value='${dis.DistrictID}'>${dis.DistrictName}</option>`);
                            });
                        }
                    }

                });

            });
        
            $(document).on('change', '#district', function() {

                var districtID = $(this).val();
                var districtName = $(this).find('option:selected').text();
                var districtFeeID = $(this).find('option:selected').val();
                $('.my-data-district').find('#info_district').val(districtName);
                $('.my-data-district').find('#districtFee').val(districtFeeID);

                $.ajax({

                    url: urlWard,
                    method: 'GET',
                    headers: {
                        token: tokenAPI,
                    },
                    data: {
                        district_id: districtID
                    },
                    success: function(response) {
                        var wards = response.data;
                        const wardData = document.querySelectorAll(".ward-data");

                        $(wardData).find('option:not(:first)').remove();

                        if(districtID != '') {
                            $.each(wards, function(key, war) {
                                $(wardData).append(`<option value='${war.WardCode}'>${war.WardName}</option>`);
                            });
                        }
                    }

                });
        
                $.ajax({

                    url: urlService,
                    method: 'GET',
                    headers: {
                        contentType : 'application/json',
                        token: tokenAPI,
                    },
                    data: {
                        shop_id:shopID,
                        from_district: 1442,
                        to_district: districtID,
                    },
                    success: function(response) {
                        var service = response.data[0];
                        const serviceData = document.querySelectorAll(".service-data");
                        $(serviceData).find('option:not(:first)').remove();
                        $(serviceData).append(`<option value='${service.service_id}'>${service.short_name}</option>`);
                    }

                });
            });
        
            $(document).on('change', '#ward', function() {
                
                var wardFeeID = $(this).find('option:selected').val();
                var wardName = $(this).find('option:selected').text();
                $('.my-data-ward').find('#wardFee').val(wardFeeID);
                $('.my-data-ward').find('#info_ward').val(wardName);

            });
        
            $(document).on('change', '#service', function(){
        
                var serviceID = parseInt($(this).val());
                var toDistrict = $('.my-data-district').find('#districtFee').val();
                var toWardCode = $('#my-ward').find('#wardFee').val();

                $.ajax({

                    url: urlFee,
                    method: 'GET',
                    timeout: 8000,
                    headers: {
                        contentType : 'application/json',
                        token: tokenAPI,
                        shop_id: shopID,
                    },
                    data: {
                        from_district_id:1442,
                        from_ward_code: 20101,
                        service_id: serviceID,
                        to_district_id:toDistrict,
                        to_ward_code: toWardCode,
                        height:20,
                        length:20,
                        weight:200,
                        width:20,
                        insurance_value:10000,
                        coupon: null
                    },
                    success: function(response) {
                        if(response) {
                            const formattedAmount = response.data.total.toLocaleString('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            });
                            const customVND = formattedAmount.replace('₫', 'VNĐ');
                            $('.my-data-ward').find('#priceFeeForm').val(response.data.total);
                            $('#totalFee').html(customVND);
                        }else {
                            alert("Hệ thống không phản hồi!")
                        }
                    },
                    error: function(xhr, status, error) {
                        if (status === "timeout") {
                            xhr.abort();
                            alert("Yêu cầu quá thời gian. Vui lòng thử lại.");
                        }
                    }
                });
            });
        }
    });

});