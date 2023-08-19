<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>OpenStreetMap &amp; OpenLayers - Marker Example</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <link rel="stylesheet" href="https://openlayers.org/en/v4.6.5/css/ol.css" type="text/css" />
    <script src="https://openlayers.org/en/v4.6.5/build/ol.js" type="text/javascript"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js" type="text/javascript"></script>


    <style>
        /*
            .map, .righ-panel {
                height: 500px;
                width: 80%;
                float: left;
            }
            */
        .map,
        .righ-panel {
            height: 98vh;
            width: 80vw;
            float: left;
        }

        .map {
            border: 1px solid #000;
        }

        .ol-popup {
            position: absolute;
            background-color: white;
            -webkit-filter: drop-shadow(0 1px 4px rgba(0, 0, 0, 0.2));
            filter: drop-shadow(0 1px 4px rgba(0, 0, 0, 0.2));
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #cccccc;
            bottom: 12px;
            left: -50px;
            min-width: 180px;
        }

        .ol-popup:after,
        .ol-popup:before {
            top: 100%;
            border: solid transparent;
            content: " ";
            height: 0;
            width: 0;
            position: absolute;
            pointer-events: none;
        }

        .ol-popup:after {
            border-top-color: white;
            border-width: 10px;
            left: 48px;
            margin-left: -10px;
        }

        .ol-popup:before {
            border-top-color: #cccccc;
            border-width: 11px;
            left: 48px;
            margin-left: -11px;
        }

        .ol-popup-closer {
            text-decoration: none;
            position: absolute;
            top: 2px;
            right: 8px;
        }

        .ol-popup-closer:after {
            content: "✖";
        }
    </style>
</head>

<body onload="initialize_map();">

    <table>

        <tr>

            <td>
                <div id="map" class="map"></div>
                <div id="map" style="width: 50vw; height: 50vh;"></div>
                <div id="popup" class="ol-popup">
                    <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                    <div id="popup-content"></div>
                </div>
                <!-- <div id="map" style="width: 80vw; height: 100vh;"></div> -->
            </td>
            <td>
                <?php
                $db = new PDO('pgsql:host=localhost;dbname=railway_vietnam;port=5432', 'postgres', '123456');
                $query = 'SELECT * FROM "ga"';
                $rs = $db->query($query);
                echo '<p>Lựa chọn tên ga tàu:</p>';
                echo '<select id="ten_ga" style="width: 150px;">';
                foreach ($rs as $row) {
                    echo '<option value="' . htmlspecialchars($row['ten_ga']) . '">' . htmlspecialchars($row['ten_ga']) . '</option>';
                }
                echo '</select>';

                ?>
                <button id="btnSeacher"> Tìm kiếm</button>
                <br />
                <br />
                <br />

                <input onclick="oncheckvn();" type="checkbox" id="vn" name="layer" value="vn"> Các tỉnh Việt Nam<br />
                <input onclick="oncheckrails();" type="checkbox" id="rails" name="layer" value="rails"> Đường ray <br />
                <input onclick="oncheckstation()" type="checkbox" id="station" name="layer" value="station"> Các điểm ga tàu <br />

                <button id="btnRest"> Làm mới</button>

            </td>
        </tr>
    </table>
    <?php include 'pgsqlAPI.php' ?>

    <script>
        var format = 'image/png';
        var map;
        var minX = 102.14457702636719;
        var minY = 8.381354331970215;
        var maxX = 109.46917724609375;
        var maxY = 23.3926944732666;

        var cenX = (minX + maxX) / 2;
        var cenY = (minY + maxY) / 2;
        var mapLat = cenY;
        var mapLng = cenX;
        var mapDefaultZoom = 5;

        var layerVN;
        var layer_rails;
        var layer_station;

        var vectorLayer;
        var styleFunction;
        var styles;
        var container = document.getElementById('popup');
        var content = document.getElementById('popup-content');
        var closer = document.getElementById('popup-closer');
        var station = document.getElementById("ten_ga");
        var chkVN = document.getElementById("vn");
        var chkStation = document.getElementById("station");
        var chkRails = document.getElementById("rails");

        var value;
        /**
         * Create an overlay to anchor the popup to the map.
         */
        // var overlay = new ol.Overlay( /** @type {olx.OverlayOptions} */ ({
        //     element: container,
        //     autoPan: true,
        //     autoPanAnimation: {
        //         duration: 250
        //     }
        // }));
        closer.onclick = function() {
            overlay.setPosition(undefined);
            closer.blur();
            return false;
        };

        function handleOnCheck(id, layer) {
            if (document.getElementById(id).checked) {
                value = document.getElementById(id).value;
                // map.setLayerGroup(new ol.layer.Group())
                map.addLayer(layer)
                vectorLayer = new ol.layer.Vector({});
                map.addLayer(vectorLayer);
            } else {
                map.removeLayer(layer);
                map.removeLayer(vectorLayer);
            }
        }

        function myFunction() {
            var popup = document.getElementById("popup");
            popup.classList.toggle("show");
        }

        function oncheckstation() {
            handleOnCheck('station', layer_station);

        }

        function oncheckrails() {
            handleOnCheck('rails', layer_rails);

        }

        function oncheckvn() {
            handleOnCheck('vn', layerVN);
        }



        function initialize_map() {

            //*
            layerBG = new ol.layer.Tile({
                source: new ol.source.OSM({})
            });

            //*/
            layerVN = new ol.layer.Image({
                source: new ol.source.ImageWMS({
                    ratio: 1,
                    url: 'http://localhost:8080/geoserver/railway/wms?',
                    params: {
                        'FORMAT': format,
                        'VERSION': '1.1.1',
                        STYLES: '',
                        LAYERS: 'railway:tinh',
                    }
                })

            });

            layer_rails = new ol.layer.Image({
                source: new ol.source.ImageWMS({
                    ratio: 1,
                    url: 'http://localhost:8080/geoserver/railway/wms?',
                    params: {
                        'FORMAT': format,
                        'VERSION': '1.1.1',
                        STYLES: '',
                        LAYERS: 'railway:duongray',
                    }
                })

            });

            layer_station = new ol.layer.Image({
                source: new ol.source.ImageWMS({
                    ratio: 1,
                    url: 'http://localhost:8080/geoserver/railway/wms?',
                    params: {
                        'FORMAT': format,
                        'VERSION': '1.1.1',
                        STYLES: '',
                        LAYERS: 'railway:ga',
                    }
                })

            });

            vectorLayer = new ol.layer.Vector({
                source: new ol.source.Vector()
            });
            var viewMap = new ol.View({
                center: ol.proj.fromLonLat([mapLng, mapLat]),
                zoom: mapDefaultZoom
            });
            map = new ol.Map({
                target: "map",
                layers: [layerBG],
                view: viewMap,

                // overlays: [overlay], //them khai bao overlays
            });
            var styles = {
                'Point': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'yellow',
                        width: 3
                    })
                }),
                'MultiLineString': new ol.style.Style({

                    stroke: new ol.style.Stroke({
                        color: 'red',
                        width: 3
                    })
                }),
                'Polygon': new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'red',
                        width: 3
                    })
                }),
                'MultiPolygon': new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: 'green'
                    }),
                    stroke: new ol.style.Stroke({
                        color: 'yellow',
                        width: 2
                    })
                })
            };
            styleFunction = function(feature) {
                return styles[feature.getGeometry().getType()];
            };
            var stylePoint = new ol.style.Style({
                image: new ol.style.Icon({
                    anchor: [0.5, 0.5],
                    anchorXUnits: "fraction",
                    anchorYUnits: "fraction",
                    src: "http://localhost:80/BTL_HTTT_DiaLy/Yellow_dot.svg"
                })
            });
            vectorLayer = new ol.layer.Vector({
                style: styleFunction
            });

            map.addLayer(vectorLayer);
            var buttonReset = document.getElementById("btnRest").addEventListener("click", () => {
                location.reload();
            })
            var button = document.getElementById("btnSeacher").addEventListener("click", () => {


                if (station && station.value && station.value.length) {
                    vectorLayer.setStyle(styleFunction);
                    $.ajax({
                        type: "POST",
                        url: "search.php",
                        data: {
                            name: station.value,
                        },
                        success: function(result, status, erro) {
                            if (result == 'null')
                                alert("không tìm thấy đối tượng");
                            else
                                highLightObj(result);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                } else {
                    alert("Nhập dữ liệu tìm kiếm");
                }
            });

            function createJsonObj(result) {
                var geojsonObject = '{' +
                    '"type": "FeatureCollection",' +
                    '"crs": {' +
                    '"type": "name",' +
                    '"properties": {' +
                    '"name": "EPSG:4326"' +
                    '}' +
                    '},' +
                    '"features": [{' +
                    '"type": "Feature",' +
                    '"geometry": ' + result +
                    '}]' +
                    '}';
                return geojsonObject;
            }

            function highLightGeoJsonObj(paObjJson) {
                var vectorSource = new ol.source.Vector({
                    features: (new ol.format.GeoJSON()).readFeatures(paObjJson, {
                        dataProjection: 'EPSG:4326',
                        featureProjection: 'EPSG:3857'
                    })
                });
                var pointStyle = new ol.style.Style({
                    image: new ol.style.Circle({
                        radius: 7,
                        fill: new ol.style.Fill({
                            color: 'blue'
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'white',
                            width: 2
                        })
                    })
                });

                var pointLayer = new ol.layer.Vector({
                    source: vectorSource,
                    style: pointStyle
                });
                var polygonStyle = new ol.style.Style({
                    fill: new ol.style.Fill({
                        color: 'orange'
                    }),
                    stroke: new ol.style.Stroke({
                        color: 'red',
                        width: 2
                    })
                });
                var boundaryStyle = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'red', // màu xanh cho đường xung quanh vùng
                        width: 3 // độ dày của đường
                    })
                });

                // Thêm lớp point vào bản đồ (giả định rằng biến `map` là bản đồ của bạn)
                map.addLayer(pointLayer);
                // console.log(vectorSource)
                vectorLayer.setSource(vectorSource);
                vectorLayer.setStyle(function(feature, resolution) {
                    return [polygonStyle, boundaryStyle];
                });
            }

            function highLightObj(result) {
                var strObjJson = createJsonObj(result);
                // console.log(strObjJson)
                var objJson = JSON.parse(strObjJson);
                highLightGeoJsonObj(objJson);
            }

            // var overlay = new ol.Overlay({
            //     element: document.getElementById('popup-content'), // 'popup' là id của một thẻ div
            //     positioning: 'bottom-center',
            //     stopEvent: false
            // });
            // map.addOverlay(overlay);

            function displayObjInfo(result, coordinate) {
                var overlay = new ol.Overlay( /** @type {olx.OverlayOptions} */ ({
                    element: container,
                    autoPan: true,
                    autoPanAnimation: {
                        duration: 250
                    }
                }));
                // Đặt nội dung cho popup
                $(container).html(result);
                map.addOverlay(overlay);

                // Đặt vị trí cho overlay (popup)
                overlay.setPosition(coordinate);

            }

            map.on('singleclick', function(evt) {
                var myPoint = 'POINT(12,5)';
                var lonlat = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
                var lon = lonlat[0];
                var lat = lonlat[1];
                var myPoint = 'POINT(' + lon + ' ' + lat + ')';

                if (value == 'vn') {
                    vectorLayer.setStyle(styleFunction);

                    $.ajax({
                        type: "POST",
                        url: "pgsqlAPI.php",
                        data: {
                            functionname: 'getInfoVNToAjax',
                            paPoint: myPoint
                        },
                        success: function(result, status, erro) {
                            displayObjInfo(result, evt.coordinate);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                    $.ajax({
                        type: "POST",
                        url: "pgsqlAPI.php",
                        data: {
                            functionname: 'getGeoVNToAjax',
                            paPoint: myPoint
                        },
                        success: function(result, status, erro) {
                            highLightObj(result);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                }
                if (value == "rails") {
                    //rails
                    vectorLayer.setStyle(styleFunction);
                    $.ajax({
                        type: "POST",
                        url: "pgsqlAPI.php",
                        data: {
                            functionname: 'getInfoRailsToAjax',
                            paPoint: myPoint
                        },
                        success: function(result, status, erro) {
                            displayObjInfo(result, evt.coordinate);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                    $.ajax({
                        type: "POST",
                        url: "pgsqlAPI.php",
                        data: {
                            functionname: 'getRailsToAjax',
                            paPoint: myPoint
                        },
                        success: function(result, status, erro) {
                            highLightObj(result);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                }
                if (value == "station") {
                    vectorLayer.setStyle(stylePoint);
                    $.ajax({
                        type: "POST",
                        url: "pgsqlAPI.php",
                        data: {
                            functionname: 'getInfoStationToAjax',
                            paPoint: myPoint
                        },
                        success: function(result, status, erro) {
                            displayObjInfo(result, evt.coordinate);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });

                    $.ajax({
                        type: "POST",
                        url: "pgsqlAPI.php",
                        data: {
                            functionname: 'getStationToAjax',
                            paPoint: myPoint
                        },
                        success: function(result, status, erro) {
                            highLightObj(result);
                        },
                        error: function(req, status, error) {
                            alert(req + " " + status + " " + error);
                        }
                    });
                }

            });
        };
    </script>
</body>

</html>