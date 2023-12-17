@extends('layouts.app')

@section('content')
    <div id="map-container">
        <div class="row">
            <div class="col-md-4">
                <div id="provinceSelectorCard">
                    <label for="provinceSelector">Choose Province:</label>
                    <select id="provinceSelector" class="form-select" onchange="changeProvince()">
                        <option value="banten">Banten</option>
                        <option value="centralJava">Central Java</option>
                        <option value="diy">Daerah Istimewa Yogyakarta (DIY)</option>
                        <option value="dkiJakarta">Daerah Khusus Ibukota (DKI) Jakarta</option>
                        <option value="eastJava">East Java</option>
                        <option value="westJava">West Java</option>
                    </select>
                </div>
                <div id="attributeSelectorCard">
                    <label for="attributeSelector">Choose Range of Age:</label>
                    <select id="attributeSelector" class="form-select" onchange="changeAttribute()">
                        <option value="U0">0-5 y.o.</option>
                        <option value="U5">5-10 y.o.</option>
                        <option value="U10">10-15 y.o.</option>
                        <option value="U15">15-20 y.o.</option>
                        <option value="U20">20-25 y.o.</option>
                        <option value="U25">25-30 y.o.</option>
                        <option value="U30">30-35 y.o.</option>
                        <option value="U35">35-40 y.o.</option>
                        <option value="U40">40-45 y.o.</option>
                        <option value="U45">45-50 y.o.</option>
                        <option value="U50">50-55 y.o.</option>
                        <option value="U55">55-60 y.o.</option>
                        <option value="U60">60-65 y.o.</option>
                        <option value="U65">65-70 y.o.</option>
                        <option value="U70">70-75 y.o.</option>
                        <option value="U75">>75 y.o.</option>
                    </select>
                </div>
            </div>
            <div class="col-md-8">
                <div id="legend">
                    <div id="legend-content"></div>
                </div>
            </div>
        </div>
        <div id="map"></div>
    </div>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v5.min.js"></script>
<script>
    let shapefileLayer = L.layerGroup();
    let shpfile;
    let selectedAttribute;
    let selectedProvince;
    const map = L.map('map');
    let bounds=L.latLngBounds([-11, 95], [-6, 113]);;

    map.setMaxBounds(bounds);
    map.on('drag', function () {
        map.panInsideBounds(bounds, { animate: false });
    });

    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        minZoom: 5,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
    const colorRanges = [
        { min: 0, max: 1000, color: '#FF0000' },
        { min: 1001, max: 2000, color: '#FFA500' },
        { min: 2001, max: 3000, color: '#FFFF00' },
        { min: 3001, max: 4000, color: '#008000' },
        { min: 4001, max: Infinity, color: '#0000FF' },
    ];

    function interpolateColor(value, colorScale) {
        const scale = d3.scaleLinear().domain(colorScale.map(color => color.min)).range(colorScale.map(color => color.color));
        return scale(value);
    }

    function updateMapColors() {
        if (selectedAttribute && shapefileLayer) {
            shapefileLayer.eachLayer(function (layer) {
                const fillColor = interpolateColor(layer.feature.properties[selectedAttribute], colorRanges);
                layer.setStyle({ fillColor: fillColor });
            });
        }
    }
    
    function changeAttribute() {
        selectedAttribute = document.getElementById('attributeSelector').value;
        // updateMapProperties(bounds, getMinZoomByProvince(selectedProvince));
        // changeProvince(); // Refresh the map with the new attribute
    //     shapefileLayer.eachLayer(function (layer) {
    //     layer.getPopup().setContent(createPopupContent(layer.feature.properties));
    // });
        if (selectedAttribute && shapefileLayer) {
        shapefileLayer.eachLayer(function (layer) {
            layer.getPopup().setContent(createPopupContent(layer.feature.properties));
        });
        updateLegend();
    }
    updateMapColors();
    }
    function updateLegend() {
    const legendContent = document.getElementById('legend-content');
    legendContent.innerHTML = '<h2>Count</h2>';

    for (const colorRange of colorRanges) {
        const legendItem = document.createElement('div');
        legendItem.innerHTML = `<span class="legend-color" style="background-color: ${colorRange.color};"></span>${colorRange.min}-${colorRange.max}`;
        legendContent.appendChild(legendItem);
    }
}

    function changeProvince() {
        selectedProvince = document.getElementById('provinceSelector').value;
        changeAttribute();

        if (shapefileLayer) {
            shapefileLayer.clearLayers();
        }

        const shapefilePath = getShapefilePath(selectedProvince);
        shpfile = new L.Shapefile(shapefilePath, {
            onEachFeature: function (feature, layer) {
                if (feature.properties) {
                    layer.bindPopup(
                        Object.keys(feature.properties)
                            .map(function (k) {
                                return k + ': ' + feature.properties[k];
                            })
                            .join('<br />'),
                        {
                            maxHeight: 200
                        }
                    );
                }
            },
            // onEachFeature: function (feature, layer) {
            //     if (feature.properties) {
            //         layer.bindPopup(
            //             Object.keys(feature.properties)
            //                 .filter(key => key === selectedAttribute) // Display only the selected attribute
            //                 .map(function (k) {
            //                     return k + ': ' + feature.properties[k];
            //                 })
            //                 .join('<br />'),
            //             {
            //                 maxHeight: 200
            //             }
            //         );
            //     }
            // },
            style: function (feature) {
                // const randomColor = getRandomColor();

                const style = {
                    // fillColor: randomColor,
                    weight: 1,
                    opacity: 1,
                    color: 'white',
                    dashArray: '3',
                    fillOpacity: 0.7
                };

                return style;
            }
        });

        shpfile.addTo(map);
        shapefileLayer = shpfile;
        bounds = getBoundsByProvince(selectedProvince);
        const minZoom = getMinZoomByProvince(selectedProvince);
        tiles.removeFrom(map);

        // const shapefileBounds = shpfile.getBounds();
        // const center = shapefileBounds.getCenter();
        // const zoom = calculateZoom(shapefileBounds, map.getSize());
        // const minZoom = 8;

        shpfile.once('data:loaded', function () {
            console.log('Shapefile loaded successfully');
            shpfile.eachLayer(function (layer) {
                shapefileLayer.addLayer(layer);
                layer.bindPopup(createPopupContent(layer.feature.properties), {
                    maxHeight: 200
                });
                layer.on('click', function (e) {
                    e.target.openPopup();
                });
            });
            shapefileLayer.addTo(map);
            updateMapProperties(bounds, minZoom);
            updateMapColors();
        });
    }
    function getBoundsByProvince(province) {
        switch (province) {
            case 'centralJava':
                return L.latLngBounds([-9, 108], [-5, 112]);
            case 'westJava':
                return L.latLngBounds([-8, 106], [-5, 109]);
            case 'eastJava':
                return L.latLngBounds([-9, 110], [-7, 115]);
            case 'diy':
                return L.latLngBounds([-8.5, 110], [-7.5, 111]);
            case 'dkiJakarta':
                return L.latLngBounds([-6.5, 106.5], [-5, 107]);
            case 'banten':
                return L.latLngBounds([-8, 105], [-5, 107]);
            default:
                return L.latLngBounds([-11, 95], [-6, 113]);
        }
    }

    function getMinZoomByProvince(province) {
        switch (province) {
            case 'centralJava':
                return 8;
            case 'westJava':
                return 8;
            case 'eastJava':
                return 7;
            case 'diy':
                return 10;
            case 'dkiJakarta':
                return 9;
            case 'banten':
                return 9;
            default:
                return 8; // Default nilai minZoom
        }
    }

    function getShapefilePath(province) {
        switch (province) {
            case 'centralJava':
                return "{{ asset('shapefile/BATAS DESA DESEMBER 2019 DUKCAPIL JAWA TENGAH.zip') }}";
            case 'westJava':
                return "{{ asset('shapefile/BATAS DESA DESEMBER 2019 DUKCAPIL JAWA BARAT.zip') }}";
            case 'eastJava':
                return "{{ asset('shapefile/BATAS DESA DESEMBER 2019 DUKCAPIL JAWA TIMUR.zip') }}";
            case 'diy':
                return "{{ asset('shapefile/BATAS DESA DESEMBER 2019 DUKCAPIL DI YOGYAKARTA.zip') }}";
            case 'dkiJakarta':
                return "{{ asset('shapefile/BATAS DESA DESEMBER 2019 DUKCAPIL DKI JAKARTA.zip') }}";
            case 'banten':
                return "{{ asset('shapefile/BATAS DESA DESEMBER 2019 DUKCAPIL BANTEN.zip') }}";
            default:
                return "";
        }
    }

    function calculateZoom(bounds, mapSize) {
        const worldDim = 256;
        const zoomMax = 19;
        let zoom = 0;
        while (worldDim < Math.max(mapSize.x, mapSize.y) && zoom < zoomMax) {
            zoom++;
            worldDim *= 2;
        }
        return zoom;
    }

    window.onload = function () {
        // map.setView([-7.150975, 110.140259], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            minZoom: 6,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        changeProvince();
    };

    function updateMapProperties(bounds, minZoom) {
        map.options.minZoom = minZoom;
        map.setMaxBounds(bounds);
        map.fitBounds(bounds);
    }

    function createPopupContent(properties) {
        // let popupContent = '<table>';
        // for (let key in properties) {
        //     popupContent += `<tr><td>${key}:</td><td>${properties[key]}</td></tr>`;
        // }
        // popupContent += '</table>';
        // return popupContent;
        // let popupContent = `<table><tr><th>${selectedAttribute}</th></tr>`;
        // popupContent += `<tr><td>${properties[selectedAttribute]}</td></tr></table>`;
        let popupContent = `<table><tr><th><h2>${properties[selectedAttribute]}</h2></th></tr>`;
        popupContent += `<tr><td>Village</td> <td>:</td><td>${properties["DESA"]}</td></tr>`;
        popupContent += `<tr><td>Subdistrict</td> <td>:</td><td>${properties["KECAMATAN"]}</td></tr>`;
        popupContent += `<tr><td>District</td> <td>:</td><td>${properties["KAB_KOTA"]}</td></tr>`;
        return popupContent;
    }
</script>
<script>
    $(document).ready(function() {
        $('#provinceSelector, #attributeSelector').select2();
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush
