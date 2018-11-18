
$("#province").change(function () {
    searchAddr();
});
$("#city").change(function () {
    searchAddr();
});
$("#district").change(function () {
    searchAddr();
});
$("#city").bind("update", function () {
    form.reRenderUI($("#city"));
});
$("#district").bind("update", function () {
    if (this.options.length == 0)
        $(this).next(".ui-wrap").hide();
    else{
        $(this).next(".ui-wrap").show();
        form.reRenderUI($(this));
    }
});
var marker = null;
$("#search_button").click(function () {
    searchAddr();
});
function setMarker(latlng) {
    if (marker == null) {
        marker = new qq.maps.Marker({
            map: map,
            position: latlng,
            zIndex: 13
        });
        marker.setDraggable(true);
        marker.index = 0;
        marker.isClicked = true;
        //setAnchor(marker, false);
        qq.maps.event.addListener(marker, 'dragend', function (e) {
            setAddress(e.latLng);
        });
    } else {
        marker.setPosition(latlng);
    }
}

function setAddress(latLng) {
    map.setCenter(latLng);
    setLatLng(latLng);
    var url = encodeURI("http://apis.map.qq.com/ws/geocoder/v1/?location=" + latLng.lat + "," + latLng.lng + "&key=" + mapkey + "&output=jsonp&&callback=?");
    $.getJSON(url, function (result) {
        if (result.result != undefined) {
            var data = result.result.address_component;
            //var province = data.province;
            var city = data.city;
            var district = data.district;
            var street = data.street;
            var street_number = data.street_number;
            var addr = street_number;
            if (addr == "")
                addr = street;
            $("#city").val(city);
            $("#district").val(district);
            document.getElementById("address").value = addr;
        } else {
            document.getElementById("address").value = "";
        }
    });
}
function setLatLng(latLng) {
    $("#longitude").val(latLng.lng);
    $("#latitude").val(latLng.lat);
}
function searchAddr() {
    var address = $("#province").val() + ',' + $("#city").val() + ',' + $("#district").val() + ',' + $("#address").val();
    //通过getLocation();方法获取位置信息值        
    geocoder.getLocation(address);
}

function setAnchor(marker, flag) {
    var left = marker.index * 27;
    if (flag == true) {
        var anchor = new qq.maps.Point(10, 30),
                origin = new qq.maps.Point(left, 0),
                size = new qq.maps.Size(27, 33),
                icon = new qq.maps.MarkerImage("http://lbs.qq.com/tool/getpoint/img/marker10.png", size, origin, anchor);
        marker.setIcon(icon);
    } else {
        var anchor = new qq.maps.Point(10, 30),
                origin = new qq.maps.Point(left, 35),
                size = new qq.maps.Size(27, 33),
                icon = new qq.maps.MarkerImage("http://lbs.qq.com/tool/getpoint/img/marker10.png", size, origin, anchor);
        marker.setIcon(icon);
    }
}

var map = null;
var geocoder = null;
function loadMap() {
    var lat = document.getElementById("latitude") || document.getElementById("lat");
    var lon = document.getElementById("longitude") || document.getElementById("lon");
    var address = document.getElementById("address");
    var addr = document.getElementById("province").value;
    addr += document.getElementById("city").value;
    addr += document.getElementById("district").value;
    addr += address.value;
    var addr = $("#province").val() + $("#city").val() + $("#district").val() + address.value;
    var zoom = 13;
    /*
     if (lat.value != "" && lon.value != "") {
     data = {"lat": lat.value, "lng": lon.value, "adr": addr};
     var latlng = new qq.maps.LatLng(lat.value, lon.value);
     setMarker(latlng);
     map.setCenter(latlng);
     map.setZoom(zoom);
     } else {
     setCenter(addr, 13);
     }    
     */
    var center = new qq.maps.LatLng(39.936273, 116.44004334);
    map = new qq.maps.Map(document.getElementById('l-map'), {

        zoom: 13
    });
    //调用地址解析类
    geocoder = new qq.maps.Geocoder({
        complete: function (result) {
            map.setCenter(result.detail.location);
            setMarker(result.detail.location);
        }
    });
    qq.maps.event.addListener(map, "click", function (e) {
        //cusorPixel
        //  if (marker != null)
        //   marker.setPosition(e.latLng);
        setAddress(e.latLng);
    });
}