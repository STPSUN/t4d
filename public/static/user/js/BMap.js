var map;
function loadBaidu_map() {    
    var data = null;
    var lat = document.getElementById("latitude") || document.getElementById("lat");
    var lon = document.getElementById("longitude") || document.getElementById("lon");
    var address = document.getElementById("address");
    var addr = document.getElementById("province").value;
    addr += document.getElementById("city").value;
    addr += document.getElementById("district").value;
    addr += address.value;
    if (lat.value != "" && lon.value != "") {
        data = {"lat": lat.value, "lng": lon.value, "adr": addr};
    }
    $(address).change(function () {
        loadmap();
    });
    map = new BMap.Map("l-map");
    var myGeo = new BMap.Geocoder();    
    var marker1;
    var marker2;
    map.enableScrollWheelZoom();
    if (data) {
        var point = new BMap.Point(data.lng, data.lat);
        marker1 = new BMap.Marker(point);        // 创建标注
        map.addOverlay(marker1);
        var opts = {
            width: 220, // 信息窗口宽度 220-730
            height: 60, // 信息窗口高度 60-650
            title: ""  // 信息窗口标题
        };
        var infoWindow = new BMap.InfoWindow("原本位置 " + data.adr + " ,移动红点修改位置!你也可以直接修改上方位置系统自动定位!", opts);  // 创建信息窗口对象
        marker1.openInfoWindow(infoWindow);      // 打开信息窗口
        doit(point);
    } else {
        var myCity = new BMap.LocalCity();
        // 将结果显示在地图上，并调整地图视野  
        myGeo.getPoint(addr, function (point) {
            doit(point);
        });
    }
    map.enableDragging();
    map.enableContinuousZoom();
    map.addControl(new BMap.NavigationControl());
    map.addControl(new BMap.ScaleControl());
    map.addControl(new BMap.OverviewMapControl());

    function auto() {
        var geolocation = new BMap.Geolocation();
        geolocation.getCurrentPosition(function (r) {
            if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                var point = new BMap.Point(r.point.lng, r.point.lat);
                marker1 = new BMap.Marker(point);        // 创建标注
                map.addOverlay(marker1);
                var opts = {
                    width: 220, // 信息窗口宽度 220-730
                    height: 60, // 信息窗口高度 60-650
                    title: ""  // 信息窗口标题
                };
                var infoWindow = new BMap.InfoWindow("定位成功这是你当前的位置!,移动红点标注目标位置，你也可以直接修改上方位置,系统自动定位!", opts);  // 创建信息窗口对象
                marker1.openInfoWindow(infoWindow);      // 打开信息窗口
                doit(point);
            } else {
                //alert('failed' + this.getStatus());
            }
        });
    }

    function doit(point) {
        if (point) {
            //lat.value = point.lat;
            //lon.value = point.lng;
            convertorQQ(point.lat,point.lng,lat,lon);
            map.setCenter(point);
            map.centerAndZoom(point, 15);
            map.panTo(point);

            var cp = map.getCenter();
            myGeo.getLocation(point, function (result) {
                if (result) {
                    //address.value = result.addressComponents.street + result.addressComponents.streetNumber;
                }
            });

            marker2 = new BMap.Marker(point);        // 创建标注  
            var opts = {
                width: 220, // 信息窗口宽度 220-730
                height: 60, // 信息窗口高度 60-650
                title: ""  // 信息窗口标题
            };
            var infoWindow = new BMap.InfoWindow("拖拽地图或红点，在地图上用红点标注您的店铺位置。", opts);  // 创建信息窗口对象
            marker2.openInfoWindow(infoWindow);      // 打开信息窗口

            map.addOverlay(marker2);                     // 将标注添加到地图中

            marker2.enableDragging();
            marker2.addEventListener("dragend", function (e) {
                //lat.value = e.point.lat;
                //lon.value = e.point.lng;
                convertorQQ(e.point.lat,e.point.lng,lat,lon);
                myGeo.getLocation(new BMap.Point(e.point.lng, e.point.lat), function (result) {
                    if (result) {
                        address.value = result.addressComponents.street + result.addressComponents.streetNumber;
                        marker2.setPoint(new BMap.Point(e.point.lng, e.point.lat));
                        map.panTo(new BMap.Point(e.point.lng, e.point.lat));
                    }
                });
            });

            map.addEventListener("dragend", function showInfo() {
                var cp = map.getCenter();
                myGeo.getLocation(new BMap.Point(cp.lng, cp.lat), function (result) {
                    if (result) {
                        address.value = result.addressComponents.street + result.addressComponents.streetNumber;
                        //lat.value = cp.lat;
                        //lon.value = cp.lng;
                        convertorQQ(cp.lat,cp.lng,lat,lon);
                        marker2.setPoint(new BMap.Point(cp.lng, cp.lat));
                        map.panTo(new BMap.Point(cp.lng, cp.lat));
                    }
                });
            });

            map.addEventListener("dragging", function showInfo() {
                var cp = map.getCenter();
                marker2.setPoint(new BMap.Point(cp.lng, cp.lat));
                map.panTo(new BMap.Point(cp.lng, cp.lat));
                map.centerAndZoom(marker2.getPoint(), map.getZoom());
            });


        }


        //}, province);


    }

    function loadmap() {
        var addr = document.getElementById("province").value;
        addr += document.getElementById("city").value;
        addr += document.getElementById("district").value;
        addr += address.value;
        var city = addr;
        var myCity = new BMap.LocalCity();
        // 将结果显示在地图上，并调整地图视野  
        myGeo.getPoint(addr, function (point) {
            if (point) {
                marker2.setPoint(new BMap.Point(point.lng, point.lat));
                //lat.value = point.lat;
                //lon.value = point.lng;
                convertorQQ(point.lat,point.lng,lat,lon);
                map.panTo(new BMap.Point(marker2.getPoint().lng, marker2.getPoint().lat));
                map.centerAndZoom(marker2.getPoint(), map.getZoom());
            }
        });
    }

    function setarrea(address, city) {
        address.value = address;
        //$('city').value=city;
        window.setTimeout(function () {
            loadmap();
        }, 2000);
    }

    function initarreawithpoint(lng, lat) {
        window.setTimeout(function () {
            marker2.setPoint(new BMap.Point(lng, lat));
            map.panTo(new BMap.Point(lng, lat));
            map.centerAndZoom(marker2.getPoint(), map.getZoom());
        }, 2000);
    }
    $("#search_button").click(function () {
        loadmap();
    });
}

//转换百度坐标为腾讯坐标
function convertorQQ(lat, lon,latContor,lonContor) {
    qq.maps.convertor.translate(new qq.maps.LatLng(lat, lon), 3, function (res) {
        var latlng = res[0];
        latContor.value=latlng.lat;
        lonContor.value=latlng.lng;       
    });
}