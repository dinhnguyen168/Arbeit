<template>
    <div :class="Object.assign({'c-dis-form': true}, themeClasses, formClasses)">
      <dis-filter-form class="c-dis-form__filter"
                       :filterByValuesModel.sync="filterByValuesModel"
                       :fields="fields" :formName="formName"
                       ref="filter"
                       :dataModels="filterDataModels"
                       :dataModel="dataModel"
                       :filterValidation = "filterValidation"
                       v-mousetrap="{keys: shortcuts.focusOnFilter, disabled: false}"
                       @mousetrap.prevent="focusOnFilter"
                       @change="(value) => filterValue = value"
                       @handleFilterValidation="handleFilterValidation"> </dis-filter-form>
      <v-expansion-panel :class="{'mb-2': true, 'current-record-compact': $store.state.compactUI}" v-model="detailSectionExpand">
        <v-expansion-panel-content>
          <template v-slot:header>
            <div v-if="!$store.state.compactUI">Current record</div>
            <div v-else></div>
          </template>
          <v-card>
              <v-form @keyup.native.enter="submit" ref="form" lazy-validation class="c-dis-form__form" v-mousetrap="{keys: shortcuts.focusOnForm, disabled: false}" @mousetrap.prevent="focusOnForm" >
                  <dis-auto-increment-input
                          v-model.number="formModel['id']"
                          :serverValidationErrors="serverValidationErrors"
                          name="id"
                          label="ID"
                          :validators="[]"
                  ></dis-auto-increment-input>
                  <!--<v-layout wrap>-->
                  <slot name="form-fields"
                        v-if="checkConditions"
                        :fields="fields"
                        :formScenario="formScenario"
                        :selectedItem="selectedItem"
                        :formModel="formModel"
                        :serverValidationErrors="serverValidationErrors"
                        :requiredFilterParams="requiredFilterParams"
                        :compactUI="$store.state.compactUI"
                  >
                  </slot>
              </v-form>
              <v-card-actions v-if="(formScenario === 'create' || formScenario === 'edit') && userCanEditForm">
                      <!-- save button -->
                      <v-btn @click="submit" v-mousetrap="{keys: shortcuts.save, disabled: isSaveButtonDisabled || !userCanEditForm}" @mousetrap.prevent="submit" class="c-dis-form__btn-save" title="Save" :loading="isSaving" :disabled="isSaveButtonDisabled">
                          <v-icon>save</v-icon> Save
                      </v-btn>
                    <!-- cancel button -->
                    <v-btn @click="onCancelClick" v-mousetrap="{keys: shortcuts.cancel, disabled: isCancelButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onCancelClick" class="c-dis-form__btn-cancel" :disabled="isCancelButtonDisabled" title="Cancel">
                        <v-icon>cancel</v-icon> Cancel
                    </v-btn>
                    <v-flex shrink class="ml-2">
                      <dis-select-auto-print :autoPrintReports="autoPrintReports" v-model="autoPrint"></dis-select-auto-print>
                    </v-flex>
              </v-card-actions>
              <v-card-actions>
                  <v-layout wrap justify-space-between class="c-dis-form__actions">
                      <v-flex shrink>
                        <slot
                          name="form-actions"
                          :onEditClick="onEditClick"
                          :onCreateNewClick="onCreateNewClick"
                          :onDeleteClick="onDeleteClick"
                          :onDuplicateClick="onDuplicateClick"
                          :isEditButtonDisabled="isEditButtonDisabled"
                          :userCanEditForm="userCanEditForm"
                          :isNewButtonDisabled="isNewButtonDisabled"
                          :isFetchingDefaults="isFetchingDefaults"
                          :isFetchingDuplicate="isFetchingDuplicate"
                          :isDeleteButtonDisabled="isDeleteButtonDisabled"
                          :shortcuts="shortcuts">
                          <!-- edit button -->
                          <v-btn @click="onEditClick" v-mousetrap="{keys: shortcuts.edit, disabled: isEditButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onEditClick"  class="c-dis-form__btn-edit" :disabled="isEditButtonDisabled || !userCanEditForm" title="Edit">
                            <v-icon>edit</v-icon> Edit
                          </v-btn>
                          <!-- new button -->
                          <v-btn ref="createNewButton" @click="onCreateNewClick" v-mousetrap="{keys: shortcuts.new, disabled: isNewButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onCreateNewClick" class="c-dis-form__btn-create" title="New" :loading="isFetchingDefaults" :disabled="isNewButtonDisabled || !userCanEditForm">
                            <v-icon>add</v-icon> New
                          </v-btn>
                          <!-- duplicate button -->
                          <v-btn @click="onDuplicateClick" v-mousetrap="{keys: shortcuts.duplicate, disabled: isEditButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onDuplicateClick"  class="c-dis-form__btn-duplicate" title="Duplicate" :loading="isFetchingDuplicate" :disabled="isEditButtonDisabled || !userCanEditForm">
                            <v-icon>queue</v-icon> Smart Copy
                          </v-btn>
                          <!-- delete button -->
                          <v-btn @click="onDeleteClick" v-mousetrap="{keys: shortcuts.delete, disabled: isDeleteButtonDisabled || !userCanEditForm}" @mousetrap.prevent="onDeleteClick"  class="c-dis-form__btn-delete" :disabled="isDeleteButtonDisabled || !userCanEditForm" title="Delete">
                            <v-icon>delete</v-icon> Delete
                          </v-btn>
                        </slot>
                      </v-flex>
                      <v-flex shrink>
                          <v-btn flat icon
                                :disabled="isTableNavigationDisabled"
                                :loading="isDataTableRefreshing"
                                @click="$refs.dataTable.selectFirstRecord()">
                              <v-icon>first_page</v-icon>
                          </v-btn>
                          <v-btn flat icon
                                :disabled="isTableNavigationDisabled"
                                :loading="isDataTableRefreshing"
                                @click="$refs.dataTable.selectPreviousRecord()">
                              <v-icon>chevron_left</v-icon>
                          </v-btn>
                          <v-btn flat icon
                                :disabled="isTableNavigationDisabled"
                                :loading="isDataTableRefreshing"
                                @click="$refs.dataTable.selectNextRecord()">
                              <v-icon>chevron_right</v-icon>
                          </v-btn>
                          <v-btn flat icon
                                :disabled="isTableNavigationDisabled"
                                :loading="isDataTableRefreshing"
                                @click="$refs.dataTable.selectLastRecord()">
                              <v-icon>last_page</v-icon>
                          </v-btn>
                          <span class="c-dis-form__record-index mr-2 ml-2">
                              {{$refs.dataTable && $refs.dataTable.currentRecordIndex ? $refs.dataTable.currentRecordIndex : 0}} / {{$refs.dataTable ? $refs.dataTable.pagination.totalItems : 0}}
                          </span>
                          <dis-select-report :disabled="!selectedItem" :reports="reports.single" v-on:select:report="onSingleRecordReportClick"></dis-select-report>
                      </v-flex>
                  </v-layout>
                  <div>
                  </div>
              </v-card-actions>
              <div>
                  <slot name="extra-form-actions" :selectedItem="selectedItem" :onSubFormClick="onSubFormClick" :onSupFormClick="onSupFormClick">
                  </slot>
                  <dis-form-files-buttons
                      v-if="formName !== 'files'"
                      @show-files-click="onShowFilesClick"
                      @upload-files-click="onUploadFilesClick"
                      :selected-item="selectedItem"
                      :filter-data-models="filterDataModels"
                      :data-model="dataModel"></dis-form-files-buttons>
              </div>
          </v-card>
        </v-expansion-panel-content>
      </v-expansion-panel>
      <v-expansion-panel v-model="listSectionExpand">
        <v-expansion-panel-content>
          <template v-slot:header>
            <div>List of records</div>
          </template>
          <dis-data-table
                    :formName="formName"
                    :updateUrl="updateUrl"
                    :updateValueOnRowClick="formScenario === 'view'"
                    ref="dataTable"
                    :refreshing.sync="isDataTableRefreshing"
                    v-model="selectedItem"
                    :dataModel="dataModel"
                    :fields="userIsDeveloper ? [{
                        label: '#',
                        name: 'id',
                        order:-1
                        }, ...fields] : fields"
                    :filterDataModels="filterDataModels"
                    :reports="reports.multiple"
                    :filterByValuesModel="filterByValuesModel"
                    :detailSectionClosed="detailSectionExpand !== 0"
                    @openDetailSection="detailSectionExpand = 0"
                    v-mousetrap="{keys: shortcuts.focusOnTable, disabled: false}"
                    @mousetrap.prevent="focusOnTable"
                    @afterRefresh="onAfterDataTableRefresh">
          </dis-data-table>
        </v-expansion-panel-content>
      </v-expansion-panel>
    </div>
</template>

<script>
import FormLocalStorage from '../util/FormLocalStorage'
import themeable from 'vuetify/lib/mixins/themeable'
import FormService from '../services/FormService'
import CrudService from '../services/CrudService'
import urlFilter from '../mixins/urlFilter'
import FORM_SHORTCUTS from '../util/shotrcuts'
import DisSelectReport from './DisSelectReport'
import DisFormFilesButtons from './DisFormFilesButtons'
import DisSelectAutoPrint from './DisSelectAutoPrint'
import debounce from '../util/debounce'

export default {
  name: 'DisForm',
  props: {
    fields: {
      type: Array,
      required: true
    },
    requiredFilters: {
      type: Array,
      required: true
    },
    calculatedFields: {
      type: Object
    },
    dataModel: {
      type: String,
      required: true
    },
    filterDataModels: {
      type: Object,
      required: true
    },
    formName: {
      type: String
    },
    updateUrl: {
      type: Boolean,
      default: true
    },
    confirmations: {
      type: Array,
      default: () => []
    }
  },
  components: {
    'dis-select-report': DisSelectReport,
    'dis-form-files-buttons': DisFormFilesButtons,
    'dis-select-auto-print': DisSelectAutoPrint
  },
  mixins: [
    themeable,
    urlFilter
  ],
  data () {
    return {
      formModel: {},
      serverValidationErrors: [],
      selectedItem: null,
      filterValue: {},
      isDataTableRefreshing: true,
      isSaving: false,
      formScenario: 'view', // view, create, edit
      isFetchingDefaults: false,
      isFetchingDuplicate: false,
      reports: {
        single: [],
        multiple: []
      },
      filterByValuesModel: {},
      detailSectionExpand: null,
      listSectionExpand: null,
      shortcuts: FORM_SHORTCUTS,
      showRecordExportMenu: false,
      autoPrint: {
        reportName: '',
        active: false
      },
      filterValidation: []
    }
  },
  watch: {
    selectedItem: function (item) {
      this.onSelectedItemChange(item)
    },
    filterByValuesModel: {
      deep: true,
      handler: function () {
        this.onFilterByValueChange()
      }
    },
    autoPrint: {
      deep: true,
      handler: function (autoPrint) {
        this.localStorage.autoPrintOnSave = autoPrint.active
        this.localStorage.autoPrintReportName = autoPrint.reportName
      }
    },
    detailSectionExpand: function (val) {
      setTimeout(() => { this.onScrollDocument() }, 500)
    },
    listSectionExpand: function (val) {
      this.onScrollDocument()
    }
  },
  created () {
    this.localStorage = new FormLocalStorage(this.formName)
    this.formService = new FormService(this.formName)
    this.crudService = new CrudService(this.dataModel)
    this.getFormReports()
    // if (this.$route.query.filter && this.updateUrl) {
    //   this.filterValue = JSON.parse(this.$route.query.filter)
    // } else if (this.formName && this.localStorage.filter) {
    //   this.filterValue = this.localStorage.filter
    // }
    if (this.formName) {
      window.name = `${this.formName.toLowerCase()}-window`
    }
    this.fields.map(field => {
      this.$set(this.formModel, field.name, null)
      // this.formModel[field.name] = null
    })
    this.$watch('formModel', {
      deep: true,
      handler: () => {
        const self = { formModel: this.formModel, ...this.formModel }
        for (const calculatedField in this.calculatedFields) {
          try {
            let cb = this.calculatedFields[calculatedField].bind(self)
            let v = cb()
            if (v || v === 0 || v === '') {
              // console.log('DisForm.vue: calculate ', calculatedField, ':', v)
              this.formModel[calculatedField] = v
            }
          } catch (e) {
            console.log('DisForm.vue: calculate error in ', calculatedField, e)
          }
        }
      }
    })
  },
  mounted () {
    this.onScrollDocument = debounce(this.onScrollDocument, 200, false)
    this.onScrollHandler = (e) => { this.onScrollDocument(e) }
    document.addEventListener('scroll', this.onScrollHandler)
    window.addEventListener('resize', this.onScrollHandler)
    this.onScrollDocument(null)
  },
  beforeUnmount () {
    document.removeEventListener('scroll', this.onScrollHandler)
    window.removeEventListener('resize', this.onScrollHandler)
  },
  beforeDestroy () {
    if (this.formName) {
      window.name = ''
    }
  },
  computed: {
    userIsDeveloper () {
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes(`sa`) || this.$store.state.loggedInUser.roles.includes(`developer`))
    },
    userCanViewForm () {
      return this.$store.state.loggedInUser && (
        this.$store.state.loggedInUser.permissions.includes(`form-${this.formName}:view`) ||
          this.$store.state.loggedInUser.permissions.includes(`form-*:view`)
      )
    },
    userCanEditForm () {
      return this.$store.state.loggedInUser && (
        this.$store.state.loggedInUser.permissions.includes(`form-${this.formName}:edit`) ||
          this.$store.state.loggedInUser.permissions.includes(`form-*:edit`)
      )
    },
    isRequiredFilterSet () {
      let isSet = true
      this.requiredFilters.map(requiredFilter => {
        if (!this.requiredFilterParams[requiredFilter.as] && !requiredFilter.skipOnEmpty) {
          isSet = false
        }
      })
      return isSet
    },
    requiredFilterParams () {
      let params = {}
      this.requiredFilters.map(element => {
        if (this.filterValue[element.value] || element.skipOnEmpty) {
          params[element.as] = this.filterValue[element.value]
        }
      })
      return params
    },
    fieldsGroups () {
      let groups = []
      this.fields.map(field => {
        let index = 0
        if (field.group) {
          index = parseInt(field.group)
        }
        if (!groups[index]) {
          groups[index] = []
        }
        groups[index].push(field)
      })
      return groups
    },
    isTableNavigationDisabled () {
      return this.formScenario === 'edit' || this.formScenario === 'create'
    },
    isSaveButtonDisabled () {
      return this.formScenario === 'view' || (this.formScenario === 'create' && !this.isRequiredFilterSet)
    },
    isEditButtonDisabled () {
      return !this.selectedItem || this.formScenario !== 'view'
    },
    isCancelButtonDisabled () {
      return this.formScenario === 'view'
    },
    isDeleteButtonDisabled () {
      return (!this.selectedItem && this.formScenario === 'view') || this.formScenario === 'create' || this.formScenario === 'edit'
    },
    isNewButtonDisabled () {
      return this.formScenario !== 'view'
    },
    formClasses () {
      return {
        'c-dis-form--view': this.formScenario === 'view',
        'c-dis-form--create': this.formScenario === 'create',
        'c-dis-form--edit': this.formScenario === 'edit'
      }
    },
    autoPrintReports () {
      // return this.reports.single
      return this.reports.single.filter((report) => { return report.canAutoPrint })
    }
  },
  methods: {
    handleFilterValidation (fieldRules) {
      this.filterValidation = [...fieldRules]
    },
    checkConditions () {
      if (this.formScenario !== 'create') {
        return this.selectedItem.length > 0
      } else {
        return true
      }
    },
    async onFilterByValueChange () {
      // Wait a bit; in sub component dataTable filterByValuesModel may not be updated, yet.
      await this.$nextTick()
      this.$refs.dataTable.refreshItems('first')
    },
    onSelectedItemChange (item) {
      if (item && this.formScenario === 'view') {
        console.log('DisForm.vue: set form model')
        this.formModel = Object.assign(this.formModel, item)
        this.detailSectionExpand = 0
        // if (!this.isRequiredFilterSet && !this.$refs.filter.filterByValues) {
        //   this.$refs.filter.setFilterFromSelectedItem(item)
        // }
        this.$router.replace(Object.assign(
          {},
          { name: this.$route.name, query: this.$route.query },
          { params: { id: item.id } })
        ).catch(err => {
          if (err.name !== 'NavigationDuplicated' && !err.message.includes('Avoided redundant navigation to current location')) {
            console.error(err)
            throw err
          }
        })
      }
      if (!item && this.formScenario === 'view') {
        console.log('DisForm.vue: set selectedItem to null')
        this.selectedItem = null
        this.clearForm()
        this.detailSectionExpand = null
        this.$router.replace(Object.assign(
          {},
          { name: this.$route.name, query: { filter: this.$route.query.filter } },
          { params: { id: null } })
        ).catch(err => {
          if (err.name !== 'NavigationDuplicated' && !err.message.includes('Avoided redundant navigation to current location')) {
            console.error(err)
            throw err
          }
        })
      }
    },
    async getFormReports () {
      try {
        let data = await this.crudService.getReports()
        this.reports.single = data.single
        this.reports.multiple = data.multiple
        // If only one autoPrintReport available not need to choose

        this.autoPrint.active = this.localStorage.autoPrintOnSave
        this.autoPrint.reportName = this.localStorage.autoPrintReportName
        if (this.autoPrintReports.length === 1) {
          this.autoPrint.reportName = this.autoPrintReports[0].name
        } else if (this.autoPrintReports.length === 0) {
          this.autoPrint.reportName = ''
        }
      } catch (error) {
        this.$dialog.notify.warning('unable to get reports')
        console.log(error)
      }
    },
    async submit () {
      if (this.$refs.form.validate()) {
        const submitConfirmations = this.confirmations.filter(item => item.on === 'submit')
        for (const submitConfirmation of submitConfirmations) {
          let confirmResult = await submitConfirmation.promise(this)
          if (!confirmResult) return
        }
        // workaround in order to include the select input of type api in the request
        for (let [key, value] of Object.entries(this.formModel)) {
          if (typeof value === 'undefined') {
            let field = this.fields.find((i) => i.name === key)
            if (typeof field !== 'undefined' && field.hasOwnProperty('formInput')) {
              if (field.formInput.type === 'select') {
                this.formModel[key] = null
              }
            }
          }
        }
        // end wordaround
        this.serverValidationErrors = []
        if (this.formModel.id) {
          // put
          this.isSaving = true
          try {
            let response = await this.formService.put(this.formModel.id, this.formModel)
            if (response.status === 200 || response.status === 204) {
              // this.clear()
              this.clearForm()
              await this.$nextTick()
              this.formModel = response.data
              console.log('Save was ok, autoPrint ...')
              if (this.autoPrint.active) this.formService.print(this.formModel.id, this.autoPrint.reportName)

              await this.$nextTick()
              await this.$refs.dataTable.refreshItems(response.data.id)
              // this.formModel = Object.assign(this.formModel, response.data)
              this.selectedItem = Object.assign({}, response.data)
              this.formScenario = 'view'
              this.$dialog.message.success('Record was updated successfully')
            }
          } catch (error) {
            if (error.response && error.response.status === 422) {
              this.serverValidationErrors = error.response.data
            } else {
              if (error.response && error.response.data && error.response.data.message) {
                this.$dialog.notify.warning(error.response.data.message, { timeout: 30000 })
              } else {
                this.$dialog.notify.warning(error.message, { timeout: 30000 })
              }
              console.log(error.message)
            }
          } finally {
            this.isSaving = false
          }
        } else {
          // post
          this.isSaving = true
          try {
            let response = await this.formService.post(Object.assign(this.formModel, this.requiredFilterParams))
            if (response.status === 201) {
              // this.formModel = Object.assign(this.formModel, response.data)
              this.selectedItem = response.data
              this.clearForm()
              await this.$nextTick()
              this.formModel = response.data
              await this.$nextTick()
              this.formScenario = 'view'
              // this.clear()
              await this.$refs.dataTable.refreshItems(response.data.id)
              this.$dialog.message.success('Record was created successfully')
            }
          } catch (error) {
            if (error.response && error.response.status === 422) {
              this.serverValidationErrors = error.response.data
            } else {
              if (error.response && error.response.data && error.response.data.message) {
                this.$dialog.notify.warning(error.response.data.message, { timeout: 30000 })
              } else {
                this.$dialog.notify.warning(error.message, { timeout: 30000 })
              }
              console.log(error.message)
            }
          } finally {
            this.isSaving = false
          }
        }
      }
    },
    clearForm () {
      this.$refs.form.reset()
      this.blurFilter()
      this.blurForm()
      this.serverValidationErrors = []
      this.formModel.id = null
    },
    filterBarWarning () {
      window.scrollTo({ top: 0, behavior: 'smooth' })
      const scrollEnd = debounce(e => {
        e.target.dispatchEvent(new CustomEvent('scroll-end', {
          bubbles: true
        }))
      }, 200, false)

      if (window.scrollY === 0) window.addEventListener('click', scrollEnd, { passive: true })
      else window.addEventListener('scroll', scrollEnd, { passive: true })

      window.addEventListener('scroll-end', () => {
        this.$dialog.error({
          title: 'Please fill out the filter bar',
          text: 'Please specify items in the filter bar. It is located at the top of the page. Select items from left to right. For editing, all pulldowns must be specified.',
          type: 'warning',
          actions: ['Ok']
        })
        this.filterValidation.push(v => !!v || 'This field is required')
        this.$refs.filter.$refs.form.validate()
      }, { once: true })
    },
    async onCreateNewClick () {
      if (!this.isRequiredFilterSet) {
        this.filterBarWarning()
      } else {
        const newConfirmations = this.confirmations.filter(item => item.on === 'new')
        for (const newConfirmation of newConfirmations) {
          let confirmResult = await newConfirmation.promise(this)
          if (!confirmResult) return
        }
        try {
          this.isFetchingDefaults = true
          let response = await this.formService.getDefaults(this.requiredFilterParams)
          this.clearForm()
          this.formScenario = 'create'
          await this.$nextTick()
          this.formModel = Object.assign({}, response.data)
          this.focusOnForm()
        } catch (error) {
          console.log(error)
          if (error.response && error.response.data && error.response.data.message) {
            this.$dialog.notify.warning(error.response.data.message, { timeout: 30000 })
          } else {
            this.$dialog.notify.warning(error.message, { timeout: 30000 })
          }
        } finally {
          this.isFetchingDefaults = false
        }
      }
    },
    async onDuplicateClick () {
      if (!this.isRequiredFilterSet) {
        this.filterBarWarning()
      } else {
        try {
          this.isFetchingDuplicate = true
          let response = await this.formService.getDuplicate(this.formModel.id, this.requiredFilterParams)
          this.clearForm()
          this.formScenario = 'create'
          await this.$nextTick()
          this.formModel = Object.assign({}, response.data)
          this.focusOnForm()
        } catch (error) {
          this.$dialog.notify.warning(error.message, { timeout: 30000 })
        } finally {
          this.isFetchingDuplicate = false
        }
      }
    },
    onEditClick () {
      this.formScenario = 'edit'
      this.focusOnForm()
    },
    onCancelClick () {
      this.clearForm()
      this.$nextTick(() => {
        this.formScenario = 'view'
        if (this.selectedItem) {
          console.log('DisForm.vue: set form model from selected')
          this.formModel = Object.assign(this.formModel, this.selectedItem)
        }
      })
    },
    async onDeleteClick () {
      let confirmed = await this.$dialog.confirm({
        title: 'Delete a record',
        text: 'Are you sure? this action could not be reverted.'
      })
      if (confirmed) {
        try {
          let response = await this.formService.delete(this.formModel.id)
          if (response.status === 200 || response.status === 202 || response.status === 204) {
            this.clearForm()
            this.formScenario = 'view'
            this.selectedItem = null
            this.$dialog.message.success('deleted successfully')
            await this.$refs.dataTable.refreshItems(false)
          }
        } catch (error) {
          this.$dialog.notify.warning('cannot delete - ' + error.message, { timeout: 30000 })
          console.log(error)
        } finally {}
      }
    },
    onSubFormClick (formName, subForm) {
      let filter = {}
      subForm.filter.map(filterUnit => {
        if (typeof this.selectedItem[filterUnit.fromField] === 'object') {
          filter[filterUnit.unit] = this.selectedItem[filterUnit.fromField].id
        } else {
          filter[filterUnit.unit] = this.selectedItem[filterUnit.fromField]
        }
      })
      let routeData = this.$router.resolve({
        query: { filter: this.filterToString(filter), selectFirst: true },
        path: subForm.url
      })
      this.openWindow(routeData.href, `${formName.toLowerCase()}-window`)
    },
    onSupFormClick (formName, supForm) {
      let filter = {}
      supForm.filter.map(filterUnit => {
        if (typeof this.selectedItem[filterUnit.fromField] === 'object') {
          filter[filterUnit.unit] = this.selectedItem[filterUnit.fromField].id
        } else {
          filter[filterUnit.unit] = this.selectedItem[filterUnit.fromField]
        }
      })
      let targetID = typeof this.selectedItem[supForm.parentIdField] === 'object' ? this.selectedItem[supForm.parentIdField].id : this.selectedItem[supForm.parentIdField]
      let routeData = this.$router.resolve({
        query: { filter: this.filterToString(filter) },
        path: supForm.url + '/' + targetID
      })
      this.openWindow(routeData.href, `${formName.toLowerCase()}-window`)
    },
    onShowFilesClick (filterQuery) {
      let routeData = this.$router.resolve({
        query: { filter: this.filterToString(filterQuery) },
        path: '/forms/files-form'
      })
      this.openWindow(routeData.href, `files-window`)
    },
    onUploadFilesClick (filterQuery) {
      let routeData = this.$router.resolve({
        query: { filter: this.filterToString(filterQuery) },
        path: '/files-upload'
      })
      this.openWindow(routeData.href, `upload-files-window`)
    },
    openWindow (url, windowName) {
      var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent) && (/^Apple Computer/i.test(navigator.vendor))
      if (!isSafari) {
        // Other browsers then safari only update existing tabs without bringing it to the front, so we try to close it before.
        let w = window.open('', windowName)
        try {
          w.close()
        } catch (e) {}
        window.open(url, windowName)
      }
    },
    blurForm () {
      for (let key in this.$refs.form.inputs) {
        this.$refs.form.inputs[key].blur && this.$refs.form.inputs[key].blur()
      }
    },
    blurFilter () {
      this.$refs.filter.blurInputs()
    },
    async focusOnFilter () {
      this.blurForm()
      await this.$nextTick()
      let filter = this.$refs.filter
      filter.$el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' })
      filter.focusInputs()
    },
    async focusOnForm () {
      this.blurFilter()
      let delay = this.detailSectionExpand !== 0 ? 300 : 0
      this.detailSectionExpand = 0 // index of the expanded panel
      await this.$nextTick()
      let form = this.$refs.form
      setTimeout(() => {
        form.$el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' })
        for (let key in form.inputs) {
          if (!form.inputs[key].disabled) {
            form.inputs[key].focus()
            break
          }
        }
      }, delay)
    },
    async focusOnTable () {
      this.blurFilter()
      this.blurForm()
      let delay = this.listSectionExpand !== 0 ? 300 : 0
      this.listSectionExpand = 0 // index of the expanded panel
      await this.$nextTick()
      let table = this.$refs.dataTable
      setTimeout(() => {
        table.$el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' })
        let focusable = table.$el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
        if (focusable.length > 0) {
          focusable[0].focus()
        }
      }, delay)
    },
    async onAfterDataTableRefresh () {
      this.listSectionExpand = 0
      await this.$nextTick()
      this.onScrollDocument()
    },
    async onSingleRecordReportClick (item) {
      const url = window.baseUrl + `report/${item.name}?model=${this.dataModel}&id=${this.selectedItem.id}`
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
    onScrollDocument (e) {
      if (this.listSectionExpand === 0) {
        const main = document.querySelector('main.v-content')
        if (main) {
          let content = main.querySelector('div.v-content__wrap')
          const actionsRow = content.querySelector('.v-datatable__actions')
          if (actionsRow) {
            let tableOverflow = content.querySelector('.v-table__overflow')
            const availableHeight = window.innerHeight - actionsRow.clientHeight - 50

            let box = tableOverflow.getBoundingClientRect()
            // console.log('availableHeight:', availableHeight, 'actionsRow.clientHeight:', actionsRow.clientHeight, 'box:', box)
            if (box.top < availableHeight) {
              let tableData = tableOverflow.querySelector('table.v-table')
              let remainHeight = Math.max(0, Math.floor(availableHeight - box.top))
              let bottomPadding = Math.max(0, Math.min(tableData.offsetHeight, availableHeight) - remainHeight - 130)
              // console.log('remainHeight', remainHeight, 'paddingBottom', bottomPadding)
              tableOverflow.style.overflowY = 'auto'
              tableOverflow.style.maxHeight = remainHeight + 'px'
              content.style.paddingBottom = bottomPadding + 'px'
            }
          }
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
