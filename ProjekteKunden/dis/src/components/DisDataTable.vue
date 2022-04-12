<template>
    <div :class="Object.assign({'c-dis-data-table': true}, themeClasses)">
      <v-menu v-model="columnsPopover" :close-on-content-click="false" offset-x :max-height="300" >
        <template #activator="data">
          <v-btn v-on="data.on" color="indigo" dark icon class="c-dis-data-table__select-columns" >
            <v-icon>view_column</v-icon>
          </v-btn>
        </template>
        <v-card>
          <v-card-text>
            <v-checkbox hide-details v-model="allColumnsSelected" :value="true" label="Select all"></v-checkbox>
            <v-checkbox
                v-for="field in fields"
                :key="field.name"
                hide-details
                v-model="selectedColumns"
                :value="field.name"
                :label="field.label"></v-checkbox>
          </v-card-text>
        </v-card>
      </v-menu>
      <div role="region" aria-label="data table" tabindex="0" class="sticky-th">
        <v-data-table
                ref="table"
                :headers="tableHeaders"
                :items="items"
                :class="{'elevation-1 c-dis-data-table__table': true, 'c-dis-data-table__table_compact': this.$store.state.compactUI}"
                :pagination.sync="pagination"
                :total-items="pagination.totalItems"
                :loading="refreshing"
                :rows-per-page-items="[5, 10, 25, 50, 100, 200]"
                v-model="selectedItems"
                select-all
                v-sortable-columns="{onEnd:sortTheHeadersAndUpdateTheKey}"
                @update:pagination="onPaginationUpdate"
        >
          <template v-slot:headers="props">
            <tr>
              <th>
                <v-checkbox :input-value="props.all" :indeterminate="props.indeterminate" hide-details @click.stop="toggleAllItems" :title="props.all ? `Deselect All` : `Select All`"></v-checkbox>
              </th>
              <th
                      v-for="header in props.headers.filter(item => item.value)"
                      :key="header.value"
                      :class="['column sortable', pagination.descending ? 'desc' : 'asc', header.value === pagination.sortBy ? 'active' : '']"
                      @click="changeSort(header.value)"
              >
                {{ header.text }}
                <v-icon small>arrow_upward</v-icon>
              </th>
            </tr>
          </template>
          <template v-slot:items="props">
            <tr :active="internalValue && internalValue.id === props.item.id" @click="() => updateValue(props.item)">
              <td>
                <v-checkbox :input-value="props.selected" hide-details @click.stop="props.selected = !props.selected"></v-checkbox>
              </td>
              <td v-for="field in selectedFields" :key="field.name">
                <a v-if="field.formatter && typeof renderField(props.item, field) === 'string' && renderField(props.item, field).startsWith('http')" :href="renderField(props.item, field)" :target="'window_' + field.name">
                  {{ renderField(props.item, field, true) }}
                </a>
                <span v-else v-html="renderField(props.item, field)"></span>
              </td>
            </tr>
          </template>
          <template v-slot:pageText="props">
              {{ props.pageStart }}-{{ props.pageStop }} of {{ props.itemsLength }}
              <v-btn flat icon
                     :disabled="(items.length && items.length === 0) || pagination.page === 1"
                     @click="setPaginationPage(1)">
                  <v-icon>first_page</v-icon>
              </v-btn>
          </template>
          <template v-slot:no-data>
              <span>
                  No data available for this filter setting. Create first record with <code>Shift+Alt+n</code>"
              </span>
          </template>
          <template v-slot:actions-append>
              <v-btn flat icon
                     :disabled="(items.length && items.length === 0) || pagination.page === Math.ceil(pagination.totalItems / pagination.rowsPerPage)"
                     @click="setPaginationPage(Math.ceil(pagination.totalItems / pagination.rowsPerPage) )">
                  <v-icon>last_page</v-icon>
              </v-btn>
              <dis-select-report :disabled="refreshing" :reports="reports" v-on:select:report="onMultiRecordsReportClick"></dis-select-report>
          </template>
        </v-data-table>
      </div>
    </div>
</template>

<script>
import FormService from '../services/FormService'
import CrudService from '../services/CrudService'
import BackendService from '../services/BackendService'
import FormLocalStorage from '../util/FormLocalStorage'
import urlFilter from '../mixins/urlFilter'
import themeable from 'vuetify/lib/mixins/themeable'
import debounce from '../util/debounce'
import Sortable from 'sortablejs'
import DisSelectReport from './DisSelectReport'

const sortableColumns = {
  inserted: (el, binding) => {
    el.querySelectorAll('th').forEach((draggableEl) => {
      // Need a class watcher because sorting v-data-table rows asc/desc removes the sortHandle class
      // watchClass(draggableEl, 'sortHandle')
      draggableEl.classList.add('sortHandle')
    })
    Sortable.create(el.querySelector('tr'), binding.value ? { ...binding.value, handle: '.column' } : {})
  }
}

export default {
  name: 'DisDataTable',
  mixins: [
    urlFilter,
    themeable
  ],
  directives: {
    'sortable-columns': sortableColumns
  },
  components: {
    'dis-select-report': DisSelectReport
  },
  props: {
    fields: {
      type: Array,
      required: true
    },
    dataModel: {
      type: String,
      required: true
    },
    value: {
      type: Object
    },
    filterDataModels: {
      type: Object
    },
    formName: {
      type: String
    },
    updateUrl: {
      type: Boolean,
      default: true
    },
    refreshing: {
      type: Boolean
    },
    updateValueOnRowClick: {
      type: Boolean,
      default: true
    },
    reports: {
      type: Array
    },
    filterByValuesModel: {
      type: Object,
      required: true
    },
    detailSectionClosed: {
      type: Boolean
    }
  },
  data () {
    return {
      items: [],
      pageCount: 0,
      pagination: {
        descending: false,
        page: 1,
        rowsPerPage: 5,
        sortBy: 'id',
        totalItems: 0
      },
      refreshOnPagination: false,
      columnsPopover: false,
      selectedColumns: [],
      storedColumns: [],
      showListExportMenu: false,
      selectedItems: [],
      internalRefreshing: true,
      internalValue: null,
      cachedFormatters: []
    }
  },
  created () {
    if (this.formName) {
      this.localStorage = new FormLocalStorage(this.formName)
      this.service = new FormService(this.formName)
      let storedSelectedColumns = this.localStorage.selectedColumns
      let storedOriginalColumns = this.localStorage.originalColumns
      if (storedSelectedColumns) {
        this.selectedColumns = Object.assign(this.selectedColumns, storedSelectedColumns)
        this.storedColumns = [...storedOriginalColumns]
      }
    } else {
      this.service = new CrudService(this.dataModel)
    }
    if (this.selectedColumns.length === 0) {
      this.selectedColumns = this.fields.map(field => field.name)
    }
    if (this.storedColumns.length === 0) {
      this.storedColumns = this.fields.map(field => field.name)
    }
    // this.refreshItems = debounce(this.refreshItems, 300)
  },
  beforeDestroy () {
    this.stopWatchingRoute()
  },
  async mounted () {
    this.startWatchingRoute()
    const selectedItemId = this.updateUrl && this.$route.params.id ? parseInt(this.$route.params.id) : undefined
    const storedPagination = !selectedItemId && this.formName ? this.localStorage.pagination : {}
    await this.$nextTick()
    if (Number.isInteger(selectedItemId)) {
      await this.refreshItems(selectedItemId, storedPagination || {})
    } else if (this.$route.query.selectFirst) {
      await this.refreshItems('first', storedPagination || {})
    } else {
      await this.refreshItems(undefined, storedPagination || {})
    }
  },
  computed: {
    filterQueryParamsString () {
      return FormService.encodeFilterParams(this.filterParams)
    },
    filterParams () {
      let params = {}
      Object.keys(this.filterDataModels).forEach(key => {
        if (this.urlFilter && this.urlFilter[key]) {
          params[this.filterDataModels[key].ref] = this.urlFilter[key]
        }
      })
      return Object.assign(params, this.filterByValuesModel)
    },
    selectedFields () {
      return this.selectedColumns.map(item => {
        return this.fields.find(field => field.name === item)
      }).filter(item => item) // this.fields.filter(field => this.selectedColumns.includes(field.name))
    },
    tableHeaders () {
      return [
        {
          text: '',
          value: '',
          sortable: false
        },
        ...this.selectedFields.map((field) => {
          return {
            text: field.label,
            value: field.name
          }
        })
      ]
    },
    currentRecordIndex () {
      if (this.internalValue) {
        let currentIndex = this.items.findIndex(item => item.id === this.internalValue.id)
        if (currentIndex > -1) {
          return ((this.pagination.page - 1) * this.pagination.rowsPerPage) + currentIndex + 1
        }
      }
      return null
    },
    isRefreshing: {
      get () {
        return this.internalRefreshing
      },
      set (v) {
        this.internalRefreshing = v
        this.$emit('update:refreshing', v)
      }
    },
    allColumnsSelected: {
      get () {
        return this.selectedColumns.length === this.fields.length
      },
      set (v) {
        if (v) {
          this.selectedColumns = this.fields.map(field => field.name)
        } else {
          this.selectedColumns = []
        }
      }
    }
  },
  methods: {
    onPaginationUpdate (pagination) {
      if (this.isRefreshing) return
      if (this.formName) {
        this.localStorage.pagination = pagination
      }
      this.refreshItems(undefined, pagination)
    },
    setPaginationPage (page) {
      this.pagination.page = page
      this.onPaginationUpdate(this.pagination)
    },
    toggleAllItems () {
      if (this.selectedItems.length) {
        this.selectedItems = []
      } else {
        this.selectedItems = this.items.slice()
      }
    },
    startWatchingRoute () {
      !this.unwatchRoute && (this.uwatchRoute = this.$watch('$route', {
        handler: debounce(async (val, oldVal) => {
          if (oldVal && (!oldVal.query.hasOwnProperty('filter') || oldVal.query.filter === '')) oldVal.query.filter = undefined
          if (!val.query.hasOwnProperty('filter') || val.query.filter === '') val.query.filter = undefined
          if (val.params.id && (!oldVal || (oldVal.params.id !== val.params.id))) {
            // if id param was changes only. 1st priority
            console.log('refresh with id')
            // await this.refreshItems(parseInt(val.params.id))
          } else if (val.query.selectFirst) {
            // if selectFirst is set in the query (when navigating from sup form to sub from i.e. cores -> sections)
            console.log('refresh with selectFirst')
            await this.refreshItems('first')
          } else if (!oldVal || (oldVal.query.filter !== val.query.filter)) {
            // if filter query was changed only. 2nd priority
            console.log('refresh withOUT id')
            // console.log('DisDataTable.routerWatcher() val:', val, ', oldVal:', oldVal)
            await this.refreshItems(false)
          }
        }, 300)
      }))
    },
    stopWatchingRoute () {
      this.uwatchRoute && this.uwatchRoute()
      this.unwatchRoute = null
    },
    /**
     * refresh data table items and select an item after loading (if specified)
     * @param selectedItemId Integer/Boolean/String false to select none, 'first' to select the first element
     * @param pagination query pagination
     * @returns {Promise<boolean>}
     */
    async refreshItems (selectedItemId, pagination = {}) {
      try {
        // this.stopWatchingPagination()
        this.isRefreshing = true
        let queryParams = {
          'per-page': this.pagination.rowsPerPage > 0 ? this.pagination.rowsPerPage : -1,
          'page': this.pagination.rowsPerPage > 0 ? this.pagination.page : 1,
          'sort': `${(this.pagination.descending) ? '-' : ''}${this.pagination.sortBy}`
        }
        queryParams = Object.assign(queryParams, pagination)
        if (Number.isInteger(selectedItemId)) {
          queryParams = Object.assign(queryParams, { 'selected-record-id': selectedItemId })
        }
        let data = await this.service.getList(queryParams, this.filterParams)
        this.items = data.items
        this.pagination.totalItems = data._meta.totalCount
        this.pageCount = data._meta.pageCount
        this.pagination.page = data._meta.currentPage
        this.pagination.rowsPerPage = queryParams.rowsPerPage ? queryParams.rowsPerPage : this.pagination.rowsPerPage
        this.$emit('afterRefresh')
      } catch (e) {
        console.log(e)
        this.$dialog.notify.error('Unable to load table items: ' + BackendService.lastError.data.message)
      } finally {
        // this.startWatchingPagination()
        this.isRefreshing = false
        await this.$nextTick()
        if (Number.isInteger(selectedItemId)) {
          let selectedItem = this.items.find(item => item.id === selectedItemId)
          if (selectedItem) {
            this.updateValue(selectedItem)
          }
        } else if (selectedItemId === false) {
          this.updateValue(null)
        } else if (selectedItemId === 'first') {
          this.selectFirstRecord()
        }
      }
    },
    async updateValue (item) {
      if (this.updateValueOnRowClick) {
        if (!item) {
          // set no none
          this.internalValue = null
          this.$emit('input', null)
          return
        } else {
          if (!this.value || !this.internalValue || item.id !== this.internalValue.id) {
            this.internalValue = item
            this.$emit('input', item)
          } else {
            // console.log('updateValue() NO $emit; item:', item.id, ' equals internalValue: ', this.internalValue.id)
          }
        }
      }
      if (this.detailSectionClosed) this.$emit('openDetailSection')
    },
    async selectFirstRecord () {
      if (this.pagination.page !== 1) {
        await this.refreshItems(undefined, Object.assign(this.pagination, { page: 1 }))
      }
      this.updateValue(this.items[0])
    },
    async selectPreviousRecord () {
      if (!this.internalValue) {
        this.selectLastRecord()
      } else {
        let currentIndex = this.items.findIndex(item => item.id === this.internalValue.id)
        if (currentIndex === -1) {
          // console.log('select last element')
          this.updateValue(this.items[this.items.length - 1])
        } else if (currentIndex > 0) {
          this.updateValue(this.items[currentIndex - 1])
        } else {
          console.log('go to previous page if exists', this.pagination.page)
          if (this.pagination.page > 1) {
            await this.refreshItems(undefined, { page: this.pagination.page - 1 })
            this.updateValue(this.items[this.items.length - 1])
          }
        }
      }
    },
    async selectNextRecord () {
      if (!this.internalValue) {
        this.selectFirstRecord()
      } else {
        let currentIndex = this.items.findIndex(item => item.id === this.internalValue.id)
        if (currentIndex === -1) {
          this.updateValue(this.items[0])
        } else if (currentIndex < this.pagination.rowsPerPage - 1 && currentIndex < this.pagination.totalItems - 1) {
          this.updateValue(this.items[currentIndex + 1])
        } else {
          if (this.pagination.page < Math.ceil(this.pagination.totalItems / this.pagination.rowsPerPage)) {
            await this.refreshItems(undefined, { page: this.pagination.page + 1 })
            this.updateValue(this.items[0])
          }
        }
      }
    },
    async selectLastRecord () {
      let lastPage = Math.ceil(this.pagination.totalItems / this.pagination.rowsPerPage)
      if (this.pagination.page !== lastPage) {
        await this.refreshItems(undefined, { page: lastPage })
      }
      this.updateValue(this.items[this.items.length - 1])
    },
    changeSort (column) {
      if (this.pagination.sortBy === column) {
        this.pagination = Object.assign({}, this.pagination, { descending: !this.pagination.descending })
      } else {
        this.pagination = Object.assign({}, this.pagination, { sortBy: column, descending: false })
      }
      this.onPaginationUpdate(this.pagination)
    },
    isHtml (str) {
      let doc = new DOMParser().parseFromString(str, 'text/html')
      return Array.from(doc.body.childNodes).some(node => node.nodeType === 1)
    },
    renderField (item, field, ignoreFormatter = false) {
      // eslint-disable-next-line no-eval
      const inputType = field.formInput ? field.formInput.type : null
      const isManyRelation = () => {
        if (field.formInput && field.formInput.hasOwnProperty('selectSource')) {
          return field.formInput.selectSource.type === 'many_relation'
        }
        return false
      }
      const isOneRelation = () => {
        if (field.formInput && field.formInput.hasOwnProperty('selectSource')) {
          return field.formInput.selectSource.type === 'one_relation'
        }
        return false
      }
      if (field.formatter && !ignoreFormatter) {
        if (!this.cachedFormatters[item.id + '_' + field.name]) {
          let self = { formModel: item, $value: item[field.name], ...item }
          let cb = function () {
            // eslint-disable-next-line no-eval
            return eval(field.formatter)
          }
          let v = ''
          try {
            v = (cb.bind(self))()
          } catch (e) {
            v = 'ERROR in formatter'
            console.error('ERROR in formatter of column', field.name, ':', e.message)
          }
          this.cachedFormatters[item.id + '_' + field.name] = v
        }
        return this.cachedFormatters[item.id + '_' + field.name]
      }

      if (isManyRelation()) {
        let displayValues = []
        if (item[field.name]) {
          for (const val of item[field.name]) {
            displayValues.push(val[field.formInput.selectSource.textField])
          }
          return displayValues.join(', ')
        }
      } else if (isOneRelation()) {
        if (item[field.name]) return item[field.name][field.formInput.selectSource.textField]
      } else {
        switch (inputType) {
          case 'switch':
            return item[field.name] ? 'yes' : 'no'
          case 'datetime':
            return item[field.name] ? this.$dateTime(item[field.name]).asUtc().formatForDisplay() : null
          case 'select':
            if (item[field.name]) {
              if (field.formInput.selectSource.type === 'api') {
                if (!field.formInput.multiple) {
                  if (item[field.name].hasOwnProperty(field.formInput.selectSource.textField)) {
                    return item[field.name][field.formInput.selectSource.textField]
                  } else {
                    return item[field.name]
                  }
                } else {
                  let displayValues = []
                  for (const val of item[field.name]) {
                    if (val.hasOwnProperty(field.formInput.selectSource.textField)) {
                      displayValues.push(val[field.formInput.selectSource.textField])
                    } else {
                      displayValues.push(val)
                    }
                  }
                  return displayValues.join(', ')
                }
              }
              if (field.formInput.selectSource.type === 'list') {
                return field.formInput.multiple ? item[field.name].join(', ') : item[field.name]
              }
            }
            return null
          default:
            return item[field.name]
        }
      }
    },
    async onMultiRecordsReportClick (item) {
      const url = window.baseUrl + `report/${item.name}?model=${this.dataModel}&columns=${this.selectedColumns.join(',')}&${this.filterQueryParamsString}${this.selectedItems && this.selectedItems.length ? `&specific-ids=${this.selectedItems.map(item => item.id).join(',')}` : ``}`
      if (item.confirmationMessage) {
        const answer = await this.$dialog.confirm({
          title: 'Please confirm!',
          text: item.confirmationMessage,
          persistent: true
        })
        if (answer) {
          window.open(url, '_blank')
        }
      } else {
        window.open(url, '_blank')
      }
    },
    sortTheHeadersAndUpdateTheKey (e) {
      const oldIndex = e.oldIndex - 1 // -1 for the checkbox
      const newIndex = e.newIndex - 1
      // see https://stackoverflow.com/questions/5306680/move-an-array-element-from-one-array-position-to-another
      const selectedColumnsTmp = this.selectedColumns
      if (newIndex >= selectedColumnsTmp.length) {
        let k = newIndex - selectedColumnsTmp.length + 1
        while (k--) {
          selectedColumnsTmp.push(undefined)
        }
      }
      selectedColumnsTmp.splice(newIndex, 0, selectedColumnsTmp.splice(oldIndex, 1)[0])
      this.selectedColumns = [...selectedColumnsTmp]
      this.storedColumns = [...selectedColumnsTmp]
    }
  },
  watch: {
    selectedColumns (val) {
      if (this.formName) {
        let newSelectedColumns = this.storedColumns.filter(item => this.selectedColumns.includes(item))
        if (this.selectedColumns.length <= this.storedColumns.length &&
            JSON.stringify(this.selectedColumns) !== JSON.stringify(newSelectedColumns)) this.selectedColumns = newSelectedColumns
        if (this.selectedColumns.length > this.storedColumns.length) this.storedColumns = this.selectedColumns
        this.localStorage.selectedColumns = val
      }
    },
    storedColumns (val) {
      if (this.formName) {
        this.localStorage.originalColumns = val
      }
    },
    value (val) {
      if (!val) this.intervalValue = null
    }
  }
}
</script>

<style scoped>

</style>
