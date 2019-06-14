function initialize()
{
  // helper vars
  var hmarkerlat = parseFloat(helper.lat)||53.565384153608754;
  var hmarkerlng = parseFloat(helper.lng)||-113.41532337466691;
  var hzoom = parseFloat(helper.zoom)||3;
  var hcenter = helper.center||"(53.565384153608754, -113.41532337466691)";
  var view_only = helper.view_only||false;

  // initialize document inputs
  if (!view_only) {
    document.getElementById("latitude").value = hmarkerlat;
    document.getElementById("longitude").value = hmarkerlng;
    document.getElementById("zoom").value = hzoom;
    document.getElementById("center").value = hcenter;
  }

  // parse center into lat and lng coordinates vars
  var hcenterlat = hcenter.match(/-*\d+\.\d+/g)[0];
  var hcenterlng = hcenter.match(/-*\d+\.\d+/g)[1];

  // Google LatLng vars
  var latlngmarker = new google.maps.LatLng(hmarkerlat, hmarkerlng);
  var latlngcenter = new google.maps.LatLng(hcenterlat, hcenterlng);

  // make Google map and drop marker
  var map = new google.maps.Map(document.getElementById("location_map_canvas"), {center: latlngcenter, zoom: hzoom} );
  var marker = new google.maps.Marker({position: latlngmarker, map: map, draggable: true});
  //marker.setMap(map);
  //map.setCenter(LatLngCenter);

  // listeners - click, zoom_changed, center_changed...

  if (!view_only) {

    google.maps.event.addListener(map, "click", function(event) {
      if (marker == undefined)
      {
        marker = new google.maps.Marker({position: event.latLng, map: map, animation: google.maps.Animation.DROP});
      }
      else
      {
        marker.setPosition(event.latLng);
      }
      //map.setCenter(event.latLng);
      document.getElementById("latitude").value = event.latLng.lat();
      document.getElementById("longitude").value = event.latLng.lng();
    });

    google.maps.event.addListener(map, "zoom_changed", function() {
      //alert(map.getZoom());
      document.getElementById("zoom").value = map.getZoom();
    });

    google.maps.event.addListener(map, "center_changed", function() {
      //alert(map.getCenter());
      document.getElementById("center").value = map.getCenter();
    });

  }
}

google.maps.event.addDomListener(window, 'load', initialize);

