<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode" :extraSettingsProps="['sitesColumnInTooltip', 'holesColumnInTooltip', 'mapInitType', 'startLatitude', 'startLongitude', 'startZoom', 'tilesBaseUrl']">
        <v-progress-linear indeterminate v-if="isLoading" />
        <vl-map ref="map" :load-tiles-while-animating="true" :load-tiles-while-interacting="true"
                data-projection="EPSG:4326" style="height: 400px">
            <vl-view ref="mapView" :zoom.sync="zoom" :center.sync="center" :rotation.sync="rotation"></vl-view>
            <vl-interaction-select :features.sync="selectedFeatures">
                <template slot-scope="select">
                    <!-- selected feature popup -->
                    <vl-overlay class="feature-popup" v-for="feature in select.features" :key="feature.id" :id="feature.id"
                                :position="pointOnSurface(feature.geometry)" :auto-pan="true" :auto-pan-animation="{ duration: 300 }">
                        <template v-slot>
                            <v-card max-width="30em" max-height="20em" class="scroll-y">
                                <v-card-title class="pb-0">
                                    <div class="title" v-if="feature.id.startsWith('hole')">
                                        Hole {{feature.properties.hole ? feature.properties.hole : ''}}
                                    </div>
                                    <div class="title" v-if="feature.id.startsWith('site')">
                                        Site {{feature.properties.site ? feature.properties.site : ''}}
                                    </div>
                                    <v-spacer></v-spacer>
                                    <a class="card-header-icon" title="Close"
                                       @click="selectedFeatures = selectedFeatures.filter(f => f.id !== feature.id)">
                                        <v-icon>close</v-icon>
                                    </a>
                                </v-card-title>
                                <v-card-text>
                                    <ul v-if="feature.id.startsWith('hole') && widget.extraSettings.holesColumnInTooltip">
                                        <li v-for="propertyName in Object.keys(feature.properties).filter(key => widget.extraSettings.holesColumnInTooltip.includes(key))" :key="propertyName">
                                            <strong>{{propertyName}}:</strong> {{feature.properties[propertyName]}}
                                        </li>
                                    </ul>
                                    <ul v-if="feature.id.startsWith('site') && widget.extraSettings.sitesColumnInTooltip">
                                        <li v-for="propertyName in Object.keys(feature.properties).filter(key => widget.extraSettings.sitesColumnInTooltip.includes(key))" :key="propertyName">
                                            <strong>{{propertyName}}:</strong> {{feature.properties[propertyName]}}
                                        </li>
                                    </ul>
                                </v-card-text>
                            </v-card>
                        </template>
                    </vl-overlay>
                    <!--// selected popup -->
                </template>
            </vl-interaction-select>

            <vl-layer-tile id="osm">
                <vl-source-osm ref="osmSource" :url="widget.extraSettings.tilesBaseUrl ? `${widget.extraSettings.tilesBaseUrl}/{z}/{x}/{y}.png` : 'https://{a-c}.tile.openstreetmap.de/{z}/{x}/{y}.png'"></vl-source-osm>
            </vl-layer-tile>

            <vl-layer-vector>
                <vl-source-vector ref="vectorSource">
                    <vl-feature :id="`site${site.id}`" v-for="site in validSites" :key="site.id" :properties="site || {}">
                        <vl-geom-point :coordinates="[site.longitude_dec, site.latitude_dec]"></vl-geom-point>
                        <vl-style-box>
                            <vl-style-icon :src="siteMarker" :scale="0.5" :anchor="[0.5, 1]"></vl-style-icon>
                        </vl-style-box>
                    </vl-feature>

                    <vl-feature :id="`hole${hole.id}`" v-for="hole in validHoles" :key="hole.id" :properties="hole || {}">
                        <vl-geom-point :coordinates="[hole.longitude_dec, hole.latitude_dec]"></vl-geom-point>
                        <vl-style-box>
                            <vl-style-icon :src="holeMarker" :scale="0.4" :anchor="[0.5, 1]"></vl-style-icon>
                        </vl-style-box>
                    </vl-feature>
                </vl-source-vector>
            </vl-layer-vector>
        </vl-map>
        <template v-slot:extraSettingsForm="{ extraSettingsFormModel }">
            <v-layout wrap>
                <v-flex xs12 md6>
                    <v-select
                            multiple
                            :items="siteColumns"
                            label="Sites Columns in Tooltip"
                            v-model="extraSettingsFormModel.sitesColumnInTooltip"></v-select>
                </v-flex>
                <v-flex xs12 md6>
                    <v-select
                            multiple
                            :items="holeColumns"
                            label="Holes Columns in Tooltip"
                            v-model="extraSettingsFormModel.holesColumnInTooltip"></v-select>
                </v-flex>
                <v-flex xs12 md6>
                    <v-radio-group label="Start zoom/position" v-model="extraSettingsFormModel.mapInitType">
                        <v-radio label="Auto" value="auto"></v-radio>
                        <v-radio label="Manual" value="manual"></v-radio>
                    </v-radio-group>
                    <v-layout row v-if="extraSettingsFormModel.mapInitType === 'manual'">
                        <v-flex sm12 md4>
                            <v-text-field label="Zoom Level" v-model="extraSettingsFormModel.startZoom" type="number"></v-text-field>
                        </v-flex>
                        <v-flex sm12 md4>
                            <v-text-field label="Longitude" v-model="extraSettingsFormModel.startLongitude" type="number"></v-text-field>
                        </v-flex>
                        <v-flex sm12 md4>
                            <v-text-field label="Latitude" v-model="extraSettingsFormModel.startLatitude" type="number"></v-text-field>
                        </v-flex>
                    </v-layout>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field label="Tiles Url" type="text" v-model="extraSettingsFormModel.tilesBaseUrl" hint="without / at the end"></v-text-field>
                </v-flex>
            </v-layout>
        </template>
    </base-widget>
</template>

<script>
import Vue from 'vue'
import { Map, TileLayer, OsmSource, StyleBox, IconStyle, Feature, PointGeom, SelectInteraction, Overlay, VectorLayer, VectorSource } from 'vuelayers'
import { findPointOnSurface } from 'vuelayers/lib/ol-ext'
import 'vuelayers/lib/style.css'
import BaseWidget from './BaseWidget'
import siteMarker from '../../assets/site-marker.png'
import holeMarker from '../../assets/hole-marker.png'
import CrudService from '../../services/CrudService'

Vue.use(Map)
Vue.use(TileLayer)
Vue.use(OsmSource)
Vue.use(StyleBox)
Vue.use(IconStyle)
Vue.use(Feature)
Vue.use(PointGeom)
Vue.use(SelectInteraction)
Vue.use(Overlay)
Vue.use(VectorLayer)
Vue.use(VectorSource)
export default {
  name: 'SitesAndHolesMapWidget',
  components: { BaseWidget },
  props: {
    widget: {
      type: Object,
      required: true
    },
    editMode: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      zoom: 2,
      center: [0, 0],
      rotation: 0,
      geolocPosition: undefined,
      siteMarker: siteMarker,
      holeMarker: holeMarker,
      selectedFeatures: [],
      isLoading: false,
      sites: [],
      holes: [],
      isMapMounted: false
    }
  },
  computed: {
    validSites () {
      return this.sites.filter(item => item.latitude_dec && item.longitude_dec)
    },
    validHoles () {
      return this.holes.filter(item => item.latitude_dec && item.longitude_dec)
    },
    siteColumns () {
      const siteModel = this.$store.state.templates.summary.models.find(item => item.fullName === 'ProjectSite')
      return siteModel ? siteModel.columns : []
    },
    holeColumns () {
      const holeModel = this.$store.state.templates.summary.models.find(item => item.fullName === 'ProjectHole')
      return holeModel ? holeModel.columns : []
    }
  },
  mounted () {
    this.getSitesAndHoles()
  },
  methods: {
    pointOnSurface: findPointOnSurface,
    async getSitesAndHoles () {
      const sitesService = new CrudService('ProjectSite')
      const holesService = new CrudService('ProjectHole')
      try {
        this.isLoading = true
        const sites = await sitesService.getList({ 'per-page': 200 })
        const holes = await holesService.getList({ 'per-page': 200 })
        this.sites = sites.items
        this.holes = holes.items
      } catch (error) {
        console.log(error)
        this.$dialog.notify.warning(error.message)
      } finally {
        this.isLoading = false
        if (this.isMapMounted) {
          this.setZoomAndPosition()
        } else {
          this.$refs.mapView.$mountPromise.then(() => {
            setTimeout(() => {
              this.setZoomAndPosition()
            }, 1000)
          })
        }
      }
    },
    setZoomAndPosition () {
      if (this.widget.extraSettings.mapInitType === 'manual') {
        this.zoom = parseInt(this.widget.extraSettings.startZoom)
        this.center = [parseFloat(this.widget.extraSettings.startLongitude), parseFloat(this.widget.extraSettings.startLatitude)]
      } else {
        this.$refs.mapView.$view.fit(this.$refs.vectorSource.$source.getExtent(), {
          size: this.$refs.map.$map.getSize(),
          duration: 1000
        })
      }
    }
  },
  watch: {
    'widget.extraSettings': {
      immediate: true,
      deep: true,
      handler: function (newValue) {
        this.getSitesAndHoles()
      }
    }
  }
}
</script>
