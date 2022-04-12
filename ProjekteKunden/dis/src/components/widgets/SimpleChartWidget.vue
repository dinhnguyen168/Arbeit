<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode" :extraSettingsProps="['model', 'xColumn', 'yColumn', 'pointsCount', 'queryFilter', 'chartType']">
        <v-progress-linear indeterminate v-if="isLoading" />
        <canvas :id="`myChart${widget.id}`" class="chart" width="100%" height="100%"></canvas>
        <v-alert :value="!isChartSettingsValid" color="warning">
            <ul>
                <li v-for="(error, index) in errors" :key="index">
                    {{error}}
                </li>
            </ul>
        </v-alert>
        <template v-slot:extraSettingsForm="{ extraSettingsFormModel }">
            <v-layout wrap>
                <v-flex xs12 md6>
                    <v-select label="Table" v-model="extraSettingsFormModel.model" :items="$store.state.templates.summary.models.map(item => item.fullName)"></v-select>
                </v-flex>
                <v-flex xs12 md6>
                    <v-select :disabled="!extraSettingsFormModel.model" label="x Axis Column" v-model="extraSettingsFormModel.xColumn"
                              :items="$store.state.templates.summary.models.find(item => item.fullName === extraSettingsFormModel.model) ? $store.state.templates.summary.models.find(item => item.fullName === extraSettingsFormModel.model).columns : []"></v-select>
                </v-flex>
                <v-flex xs12 md6>
                    <v-select :disabled="!extraSettingsFormModel.model" label="y Axis Column" v-model="extraSettingsFormModel.yColumn"
                              :items="$store.state.templates.summary.models.find(item => item.fullName === extraSettingsFormModel.model) ? $store.state.templates.summary.models.find(item => item.fullName === extraSettingsFormModel.model).columns : []"></v-select>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field type="number" label="Points Count" v-model="extraSettingsFormModel.pointsCount"></v-text-field>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field type="string" hint="write filter condition as a url query string 'col1Name=value1&col2Name=value2'" label="Query Filter" v-model="extraSettingsFormModel.queryFilter"></v-text-field>
                </v-flex>
                <v-flex xs12 md6>
                    <v-select :items="['line', 'pie', 'bar', 'radar', 'doughnut']" hint="select how you want to draw the chart'" label="Chart Type" v-model="extraSettingsFormModel.chartType"></v-select>
                </v-flex>
            </v-layout>
        </template>
    </base-widget>
</template>

<script>
import BaseWidget from './BaseWidget'
import CrudService from '../../services/CrudService'
import Chart from 'chart.js'

export default {
  name: 'SimpleChartWidget',
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
      isLoading: false,
      xValues: [],
      yValues: [],
      myChart: null,
      errors: []
    }
  },
  mounted () {
    this.isChartSettingsValid && this.updateChartValues()
  },
  computed: {
    chartOptions () {
      return {
        type: this.widget.extraSettings.chartType || 'line',
        data: {
          labels: this.xValues,
          datasets: [
            {
              fill: false,
              label: this.widget.extraSettings.yColumn,
              data: this.yValues,
              borderColor: [
                'rgb(255, 99, 132)', 'rgb(255, 159, 64)', 'rgb(255, 205, 86)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)', 'rgb(201, 203, 207)'
              ],
              backgroundColor: [
                'rgb(255, 99, 132)', 'rgb(255, 159, 64)', 'rgb(255, 205, 86)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)', 'rgb(201, 203, 207)'
              ]
            }
          ]
        },
        options: {
          tooltips: { mode: 'index', intersect: false },
          hover: { mode: 'nearest', intersect: true },
          scales: {
            xAxes: [{
              display: this.widget.extraSettings.chartType === 'line',
              scaleLabel: {
                display: this.widget.extraSettings.chartType === 'line',
                labelString: this.widget.extraSettings.xColumn
              }
            }]
          }
        }
      }
    },
    isChartSettingsValid () {
      return !!this.modelTemplate && this.errors.length === 0
    },
    modelTemplate () {
      return this.$store.state.templates.summary.models.find(item => item.fullName === this.widget.extraSettings.model)
    },
    filterObject () {
      const queryFilter = this.widget.extraSettings.queryFilter
      if (!queryFilter) {
        return {}
      }
      const paramsArray = queryFilter.split('&')
      const filterObject = {}
      for (let i = 0; i < paramsArray.length; i++) {
        const filterArray = paramsArray[i].split('=')
        if (filterArray.length === 2) {
          filterObject[filterArray[0]] = filterArray[1]
        }
      }
      return filterObject
    }
  },
  methods: {
    validateSettings () {
      this.errors = []
      if (!this.widget.extraSettings.model) {
        this.errors.push('Table cannot be empty')
      }
      if (!this.widget.extraSettings.xColumn) {
        this.errors.push('x Axis Column cannot be empty')
      }
      if (!this.widget.extraSettings.yColumn) {
        this.errors.push('y Axis Column cannot be empty')
      }
      for (let filterColumn in this.filterObject) {
        if (!this.modelTemplate.columns.includes(filterColumn)) {
          this.errors.push(`${filterColumn} in Query Filter does not exist`)
        }
      }
    },
    async getChartPoints (extraSettings) {
      try {
        this.isLoading = true
        if (!this.isChartSettingsValid) return
        const crudService = new CrudService(extraSettings.model)
        // }
        const data = await crudService.getList({
          'per-page': extraSettings.pointsCount,
          'fields': `${extraSettings.xColumn},${extraSettings.yColumn}`,
          'sort': '-' + extraSettings.xColumn
        }, this.filterObject)
        this.xValues = data.items.map(item => item[extraSettings.xColumn])
        this.yValues = data.items.map(item => item[extraSettings.yColumn])
        // console.log(data.items)
      } catch (error) {
        this.$dialog.notify.warning(error.message)
      } finally {
        this.isLoading = false
      }
    },
    async updateChartValues () {
      await this.getChartPoints(this.widget.extraSettings)
      this.myChart && this.myChart.destroy()
      this.myChart = new Chart(`myChart${this.widget.id}`, this.chartOptions)
    }
  },
  watch: {
    'widget.extraSettings': {
      deep: true,
      handler: function (newValue, oldValue) {
        this.validateSettings()
        this.isChartSettingsValid && this.updateChartValues()
      }
    },
    modelTemplate (newVal) {
      if (newVal) {
        this.isChartSettingsValid && this.getChartPoints(this.widget.extraSettings)
      }
    }
  }
}
</script>

<style>
    .chart {
        background-color: #fcfcfc;
    }
</style>
