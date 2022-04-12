<template>
    <v-container fluid grid-list-xl>
        <DisFileUpload @uploaded="addUploadedFile" :uploadPath="uploadPath"/>
        <v-layout row wrap>
            <v-flex sm12 md6 lg8 xl9>
                <v-data-table
                        v-model="selected"
                        :headers="headers"
                        :items="items"
                        item-key="name"
                        select-all
                        class="uploadFiles"
                        :loading="isTableRefreshing"
                        :pagination.sync="pagination"
                        :totalItems="totalItems"
                >
                    <template v-slot:items="props">
                        <tr v-if="(props.item.mime > '' || props.item.mime.size > 0)" :active="activeFile && activeFile.fullName === props.item.fullName" @click.prevent.stop="() => updateActiveFile(props.item)">
                            <td>
                                <v-checkbox
                                        v-model="props.selected"
                                        primary
                                        hide-details
                                        :disabled="!fileMatchesTemplate(props.item.name)"
                                ></v-checkbox>
                            </td>
                            <td>{{ props.item.name }}</td>
                            <td>{{ props.item.mime }}</td>
                            <td>{{ props.item.size_h }}</td>
                            <td>{{ props.item.modified }}</td>
                        <tr/>
                      <tr class="folder" v-if="props.item.mime == '' && props.item.size === 0" @click.prevent.stop="() => changeUploadPath(props.item)">
                        <td>
                        </td>
                        <td class="folder-name">{{ props.item.name === '..' ? '&larr; back' : props.item.name }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                      <tr/>
                    </template>
                </v-data-table>

                <v-form class="mt-4">
                    <v-card>
                        <v-card-title></v-card-title>
                        <v-card-text>
                            <v-layout row wrap>
                                <v-flex sm12 lg6>
                                  <v-combobox
                                      v-model="filenameTemplate"
                                      :items="listValues"
                                      label="Template to set filters by file name. (This overrides the settings below.)"
                                      clearable
                                      :append-outer-icon="'list_alt'"
                                      @click:append-outer="editTemplatesDialog = true">
                                  </v-combobox>

                                  <v-autocomplete v-for="assignId in Object.keys(assignIds).filter(item => item !== 'person')" :key="assignId" v-model="assignIds[assignId].value" :loading="isLoadingList"
                                  :items="assignIds[assignId].listItems" :label="assignId" clearable
                                  :prepend-icon="assignId !== 'expedition' ? 'refresh' : null" :error-messages="getFieldErrors(assignId)"
                                  @click:prepend="(v) => { assignId !== 'expedition' && updateCascadeList(assignIds[assignId].parent, assignIds[assignIds[assignId].parent].value) }"
                                  @change="(v) => updateCascadeList(assignId, v)"></v-autocomplete>
                                </v-flex>
                                <v-flex sm12 lg6>
                                    <dis-select-input
                                            style="padding: 0"
                                            name="fileType"
                                            label="Type"
                                            :serverValidationErrors="serverValidationErrors"
                                            :selectSource="{ 'type':'list', 'listName': 'UPLOAD_FILE_TYPE', 'textField':'remark', 'valueField':'display' }"
                                            :allowFreeInput="false"
                                            :multiple="false"
                                            v-model="fileType"></dis-select-input>
                                    <v-text-field :error-messages="getFieldErrors('number')" v-model="number" label="Number (i.e. Core box number)"></v-text-field>
                                    <v-textarea :error-messages="getFieldErrors('comment')" v-model="comment" label="Additional Information"></v-textarea>
                                    <dis-date-time-input
                                      data-input="true"
                                      name="fileDate"
                                      v-model="fileDate"
                                      label="* Upload Date"
                                      :serverValidationErrors="serverValidationErrors"
                                    ></dis-date-time-input>
                                <div v-if="assignIds.hasOwnProperty('person')">
                                  <v-autocomplete v-model="assignIds['person'].value" :loading="isLoadingList"
                                  :items="assignIds['person'].listItems"
                                  label="* Uploaded By" clearable
                                  :error-messages="getFieldErrors('person')"
                                  @click:prepend="(v) => { assignIds[assignIds['person'].parent].value }"></v-autocomplete>
                                </div>
                                </v-flex>
                                <v-flex xs12>
                                    <v-alert v-if="serverValidationErrors.length" :value="true" type="error">
                                        <ul>
                                            <li v-for="(serverError, index) in serverValidationErrors" :key="index">
                                                <strong>{{serverError.field}}</strong>: {{serverError.message}}
                                            </li>
                                        </ul>
                                    </v-alert>
                                </v-flex>
                            </v-layout>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn color="green" :loading="isAssignLoading" @click="assignSelectedFiles" :disabled="selected.length === 0">Assign Selected Files ({{selected.length}})</v-btn>
                            <v-btn color="red" :loading="isDeletingLoading" @click="deleteSelectedFiles" :disabled="selected.length === 0">Delete Selected Files ({{selected.length}})</v-btn>
                            <v-btn :disabled="selected.length !== 1" color="brown darken-2" dark @click="openImportDialog" v-if="isUserAllowedToImport">Import data</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-form>
            </v-flex>
            <v-flex sm12 md6 lg4 xl3>
                <div class="meta-data v-card v-sheet theme--light" v-if="activeFileMetaData">
                    <div class="preview" v-if="activeFileMetaData.thumbnail && activeFile && activeFile.mime.startsWith('image/')">
                      <h3>Preview of</h3><div>.{{activeFile.fullName}}</div>
                      <v-img class="meta-data__thumbnail" :alt="`.${activeFile.fullName}`" :title="`.${activeFile.fullName}`" v-if="activeFileMetaData.thumbnail && activeFile && activeFile.mime.startsWith('image/')" :src="activeFileMetaData.thumbnail" />
                    </div>
                    <h3 class="preview" v-else>No preview available for &apos;.{{activeFile.fullName}}&apos;</h3>
                    <div class="meta-data__section" v-for="section in Object.keys(activeFileMetaData).filter(item => item !== 'thumbnail')" :key="section">
                        <h3>{{section}}</h3>
                        <table>
                            <tr v-for="(val, metaKey) in activeFileMetaData[section]" :key="metaKey">
                                <td>{{metaKey}}</td>
                                <td class="ow">{{val}}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </v-flex>
            <v-dialog v-model="importerDialog" persistent max-width="400px">
                <v-card>
                    <v-form ref="importForm">
                        <v-card-title>
                            <span class="headline">Import</span>
                        </v-card-title>
                        <v-card-text>
                            <v-container grid-list-md>
                                <v-layout wrap>
                                    <v-flex xs12>
                                        <v-text-field label="Import File" :value="selected[0] ? selected[0].name : null" disabled required></v-text-field>
                                    </v-flex>
                                    <v-divider></v-divider>
                                    <v-flex xs12>
                                        <v-select
                                                v-model="importerFormModel.importerName"
                                                :items="importersList"
                                                label="Importer*"
                                                :rules="[value => !!value || 'this field is required.']"
                                                required
                                        ></v-select>
                                    </v-flex>
                                    <v-flex xs12>
                                        <v-autocomplete
                                                :disabled="importerFormModel.importerName === 'ListValues'"
                                                v-model="importerFormModel.dataModel"
                                                :rules="[validateDateModel]"
                                                :items="$store.state.templates.summary.models.map(item => item.fullName)"
                                                label="Data Model (Linked Table)"
                                        ></v-autocomplete>
                                    </v-flex>
                                    <v-flex xs12>
                                        <v-checkbox v-model="importerFormModel.dryRun" label="Only test data, do NOT import" hide-details></v-checkbox>
                                        <v-checkbox v-model="importerFormModel.stopAtFirstError" label="Stop at first error" hide-details></v-checkbox>
                                        <v-checkbox v-model="importerFormModel.delete" label="DELETE data set shown above, undo import" hide-details></v-checkbox>
                                    </v-flex>
                                </v-layout>
                            </v-container>
                        </v-card-text>
                        <v-card-actions>
                            <v-btn color="red darken-1" dark @click="importerDialog = false">Cancel</v-btn>
                            <v-spacer></v-spacer>
                            <v-btn color="brown darken-2" dark @click="onImportClick">Start import</v-btn>
                        </v-card-actions>
                    </v-form>
                </v-card>
            </v-dialog>
          <v-dialog v-model="editTemplatesDialog" fullscreen hide-overlay transition="dialog-bottom-transition">
            <v-card>
              <v-toolbar dark color="primary">
                <v-btn icon dark @click.native="closeTemplatesDialog">
                  <v-icon>close</v-icon>
                </v-btn>
                <v-toolbar-title>List Values [{{valueList}}]</v-toolbar-title>
                <v-spacer></v-spacer>
              </v-toolbar>
              <v-card-text>
                <dis-list-values-form v-if="editTemplatesDialog" :listName="valueList"></dis-list-values-form>
              </v-card-text>
            </v-card>
          </v-dialog>
        </v-layout>
    </v-container>
</template>

<script>
// import FormLocalStorage from '../util/FormLocalStorage'
// import BackendService from '../services/BackendService'
import FileService from '../services/FileService'
import urlFilter from '../mixins/urlFilter'
import ListValuesService from '@/services/ListValuesService'
export default {
  name: 'ArchiveFileUpload',
  mixins: [
    urlFilter
  ],
  data () {
    return {
      uploadPath: '/',
      selected: [],
      items: [],
      headers: [
        { text: 'Files', value: 'name' },
        { text: 'Mime', value: 'mime' },
        { text: 'Size', value: 'size' },
        { text: 'Modified', value: 'modified' }
      ],
      isTableRefreshing: false,
      pagination: {
        descending: false,
        page: 1,
        rowsPerPage: 5,
        sortBy: 'id',
        totalItems: 0
      },
      totalItems: 0,
      assignIds: {},
      refreshOnPagination: true,
      isLoadingList: false,
      fileType: null,
      fileDate: null,
      analyst: null,
      number: null,
      remarks: null,
      isAssignLoading: false,
      serverValidationErrors: [],
      activeFile: null,
      activeFileMetaData: null,
      isDeletingLoading: false,
      importerDialog: false,
      editTemplatesDialog: false,
      // isImporterDialogLoading: false,
      importerFormModel: {
        importerName: null,
        dataModel: null,
        dryRun: true,
        stopAtFirstError: false,
        delete: false
      },
      valueList: 'IMPORT_FILENAME_TEMPLATES',
      listValues: [],
      filenameTemplate: '',
      filenameTemplateRegExp: null
    }
  },
  created () {
    this.service = new FileService()
    this.$store.dispatch('importers/refreshList')

    this.valueListService = new ListValuesService('ListValues')
  },
  async mounted () {
    // expedition: { value: null, listItems: [], column: 'expedition_id' },
    // site: { value: null, listItems: [], column: 'site_id', 'parent': 'expedition' },
    // hole: { value: null, listItems: [], column: 'hole_id', 'parent': 'site' },
    // core: { value: null, listItems: [], column: 'core_id', 'parent': 'hole' },
    // section: { value: null, listItems: [], column: 'section_id', 'parent': 'core' },
    // sectionSplit: { value: null, listItems: [], column: 'section_split_id', 'parent': 'section' },
    // sample: { value: null, listItems: [], column: 'sample_id', 'parent': 'sectionSplit' },
    // request: { value: null, listItems: [], column: 'request_id', 'parent': 'sample' }
    await this.$store.dispatch('templates/getFormTemplate', 'files')
    const filterDataModels = this.$store.state.templates.forms.find(items => items.dataModel === 'ArchiveFile').filterDataModels
    const assignIds = {}
    for (const item in filterDataModels) {
      assignIds[item] = { value: null, listItems: [], column: filterDataModels[item].ref }
    }
    this.assignIds = assignIds

    this.getValueListItems()
    await this.updateCascadeList('', 0)
    if (this.urlFilter) {
      for (const item in filterDataModels) {
        if (this.urlFilter[item]) {
          this.assignIds[item].value = this.urlFilter[item]
          await this.updateCascadeList(item, this.urlFilter[item])
        }
      }
    }
  },
  methods: {
    getValueListItems () {
      this.valueListService.getList({ 'sort': 'sort' }, { 'listname': this.valueList })
        .then(data => {
          this.listValues = data.items.map(item => {
            return {
              text: item.display + (item.remark && item.remark !== item.display ? ' (' + item.remark + ')' : ''),
              value: item.display
            }
          })
          console.log('')
        })
    },
    closeTemplatesDialog () {
      this.getValueListItems()
      this.editTemplatesDialog = false
    },
    async refreshItems () {
      this.headers[0].text = 'Files in upload directory \'' + this.uploadPath + '\''
      this.activeFileMetaData = null

      this.isTableRefreshing = true
      this.refreshOnPagination = false
      try {
        let queryParams = this.pagination.rowsPerPage > 0 ? {
          'path': this.uploadPath,
          'per-page': this.pagination.rowsPerPage,
          'page': this.pagination.page,
          'sort': `${(this.pagination.descending) ? '-' : ''}${this.pagination.sortBy}`
        } : {
          'path': this.uploadPath,
          'per-page': -1,
          'sort': `${(this.pagination.descending) ? '-' : ''}${this.pagination.sortBy}`
        }
        let data = await this.service.get(queryParams)
        this.items = data.items
        // this.pagination.totalItems = data._meta.totalCount
        this.totalItems = data._meta.totalCount
        this.pagination.rowsPerPage = data._meta.perPage
        this.pagination.page = data._meta.currentPage
      } catch (e) {
        this.$dialog.notify.warning('unable to get files list')
        console.log(e)
      } finally {
        this.refreshOnPagination = true
        this.isTableRefreshing = false
        console.log('refresh: selected=', this.selected)
        let fullNames = []
        this.items.forEach((item) => {
          fullNames.push(item.fullName)
        })
        this.selected = this.selected.filter((item) => {
          return fullNames.includes(item.fullName)
        })
      }
    },
    async updateCascadeList (fieldName, val) {
      console.log(fieldName, val)
      this.isLoadingList = true
      try {
        let data = await this.service.updateSelectValues(fieldName, val)
        for (let key in data) {
          this.assignIds[key].listItems = data[key]
        }
      } catch (e) {
        this.$dialog.notify.warning('unable to get select list options')
        console.log(e)
      } finally {
        this.isLoadingList = false
      }
    },
    async assignSelectedFiles () {
      this.isAssignLoading = true
      this.serverValidationErrors = []
      const assignIds = {}
      for (const item in this.assignIds) {
        assignIds[item] = this.assignIds[item].value
      }
      try {
        let num = await this.service.assign({
          'assignIds': assignIds,
          'filenameTemplate': (this.filenameTemplate && this.filenameTemplate.value ? this.filenameTemplate.value : this.filenameTemplate),
          'fileType': this.fileType,
          'fileDate': this.fileDate,
          'analyst': this.analyst,
          'number': this.number,
          'remarks': this.remarks,
          'selectedFilenames': this.selected.map(item => item.fullName),
          'actionSave': true
        })
        this.$dialog.message.success(`${num} files were assigned`)
        this.refreshItems()
      } catch (error) {
        // const response = BackendService.lastError
        if (error.response && error.response.status === 422) {
          this.serverValidationErrors = error.response.data
        } else {
          this.$dialog.notify.warning('error happened while trying to assign files')
          console.log(error)
        }
        this.refreshItems()
      } finally {
        this.isAssignLoading = false
      }
    },
    async deleteSelectedFiles () {
      console.log('deleteSelectedFiles:', this.selected)
      this.isDeletingLoading = true
      this.serverValidationErrors = []
      try {
        await this.service.delete({
          'selectedFilenames': this.selected.map(item => item.fullName),
          'actionDelete': true
        })
        this.$dialog.message.success(`files were deleted successfully`)
        this.refreshItems()
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.serverValidationErrors = error.response.data
        } else {
          this.$dialog.notify.warning('error happened while trying to delete files')
          console.log(error)
        }
      } finally {
        this.isDeletingLoading = false
      }
    },
    getFieldErrors (name) {
      return this.serverValidationErrors.filter(item => item.field === name).map(item => item.message)
    },
    addUploadedFile (item) {
      // this.items.push(item)
      this.refreshItems()
    },
    async updateActiveFile (file) {
      if (!this.activeFile || (this.activeFile && file.fullName !== this.activeFile.fullName) || !this.activeFileMetaData) {
        this.activeFile = file
        this.activeFileMetaData = null
        try {
          const metaData = await this.service.metaData(file.fullName)
          if (this.activeFile && metaData.filename === this.activeFile.fullName) {
            delete metaData['filename']
            this.activeFileMetaData = metaData
          }
        } catch (error) {
          this.$dialog.notify.warning('error while getting file meta data')
          console.log(error)
        }
      }
    },
    async changeUploadPath (file) {
      if (file.name === '..') {
        this.uploadPath = this.uploadPath.replace(/[^/]+\/$/, '')
      } else {
        this.uploadPath += file.name + '/'
      }
      console.log('uploadPath', this.uploadPath)
      this.refreshItems()
    },
    fileMatchesTemplate (filename) {
      if (this.filenameTemplateRegExp) {
        // console.log('fileMatchesTemplate', filename, this.filenameTemplateRegExp, this.filenameTemplateRegExp.test(filename))
        return this.filenameTemplateRegExp.test(filename)
      } else {
        // console.log('fileMatchesTemplate', filename, this.filenameTemplateRegExp, true)
        return true
      }
    },
    async onImportClick () {
      let safe = true
      if (this.importerFormModel.delete) {
        safe = await this.$dialog.confirm({
          title: 'Delete Records',
          text: `Are you sure you want to delete the records defined in the import file?`
        })
      }
      if (this.$refs.importForm.validate() && safe) {
        let url = `${this.baseUrl}importer/${this.importerFormModel.importerName}?filename=` +
                    encodeURIComponent(`${this.selected[0] && this.selected[0].fullName}`) +
                    `&modelName=${this.importerFormModel.dataModel}` +
                    `&stopOnErrors=${this.importerFormModel.stopAtFirstError}` +
                    `&dryRun=${this.importerFormModel.dryRun}` +
                    `&deleteRecords=${this.importerFormModel.delete}`
        window.open(url)
      }
    },
    openImportDialog () {
      this.importerFormModel.importerName = null
      this.importerFormModel.dataModel = null
      this.importerFormModel.dryRun = true
      this.importerFormModel.stopAtFirstError = false
      this.importerDialog = true
    },
    validateDateModel (value) {
      if (this.importerFormModel.importerName) {
        let importer = this.$store.state.importers.list.find(item => item.class === this.importerFormModel.importerName)
        if (importer && importer.modelNameRequired && !value) {
          return 'model name is required.'
        }
      }
      return true
    }
  },
  computed: {
    isUserAllowedToImport () {
      console.log(this.$store.state.loggedInUser)
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes('operator') || this.$store.state.loggedInUser.roles.includes('developer') || this.$store.state.loggedInUser.roles.includes('sa'))
    },
    importersList () {
      if (this.$store.state.importers.list && this.$store.state.importers.list.length > 0) {
        return this.$store.state.importers.list
          .filter(item => this.selected[0] && this.selected[0].name.match(item.extensionRegExp))
          .map(item => {
            return { text: item.title, value: item.class }
          })
      }
      return []
    },
    baseUrl () {
      return window.baseUrl
    }
  },
  watch: {
    pagination: {
      deep: true,
      immediate: false,
      handler (v, ov) {
        if (this.refreshOnPagination) {
          this.refreshItems()
        }
      }
    },
    'importerFormModel.importerName' (newValue) {
      if (newValue === 'ListValues') {
        this.importerFormModel.dataModel = null
      }
    },
    filenameTemplate (val) {
      if (val && val.value) val = val.value
      console.log('watch filenameTemplate', val)
      if (val && val.trim().length) {
        val = val.replace(/(^\^|\$$)/, '').replace(/\./, '\\.').replace(/<[^>]+>/g, '([0-9a-zA-Z]+)').replace(/\*/g, '.*?')
        val = ('^' + val + '$')
        this.filenameTemplateRegExp = new RegExp(val)
        this.selected = this.selected.filter((v) => {
          // console.log('filter selected:', v.name)
          return this.fileMatchesTemplate(v.name)
        })
      } else {
        this.filenameTemplateRegExp = null
      }
    }
  }
}
</script>

<style scoped lang="scss">
    .meta-data {
        &__thumbnail {}
        &__section {
            table {
                border-collapse: collapse;
                td:first-child {
                    font-weight: bold;
                    padding-right: 10px;
                }
                td {
                    vertical-align: top;
                    border: dashed 1px rgba(0, 0, 0, 0.3);
                    padding: 5px;
                }
            }
          padding-bottom: 1em;
        }
        .preview {
          padding-bottom: 1em;
        }
        .ow {
          overflow-wrap: break-word;
          word-wrap: break-word;
          hyphens: auto;
        }
        background-color: lightgrey;
    }
</style>

<style lang="scss">
  div.uploadFiles table.v-datatable tr.folder {
    td.folder-name {
      text-decoration: underline;
      font-weight: bold;
    }

    cursor: pointer;
  }

</style>
