<template>
  <div class="d-inline-flex">
    <v-btn v-if="modelHasFiles" round :disabled="showFilesButtonDisabled" @click="onShowFilesClick" color="blue-grey">
      show files ({{ relatedFilesCount }})
    </v-btn>
    <v-btn v-if="modelHasFiles" round :disabled="!selectedItem" @click="onUploadFilesClick" dark color="blue-grey darken-2">
      upload files
    </v-btn>
  </div>
</template>

<script>
import CrudService from '../services/CrudService'

export default {
  name: 'DisFormFilesButtons',
  props: {
    selectedItem: { type: Object },
    dataModel: { required: true, type: String },
    filterDataModels: { required: true, type: Object }
  },
  data () {
    return {
      relatedFilesCount: 0
    }
  },
  computed: {
    archiveFileTemplate () {
      return this.$store.state.templates.models.find(item => item.fullName === 'ArchiveFile')
    },
    filesFormTemplate () {
      return this.$store.state.templates.forms.find(item => item.name === 'files')
    },
    currentModelTemplate () {
      return this.$store.state.templates.models.find(item => item.fullName === this.dataModel)
    },
    modelHasFiles () {
      console.log(this.archiveFileTemplate)
      return this.currentModelTemplate && this.archiveFileTemplate &&
          Object.keys(this.archiveFileTemplate.relations)
            .findIndex(item => this.archiveFileTemplate.relations[item].foreignTable === this.currentModelTemplate.table) > -1
    },
    showFilesButtonDisabled () {
      return !this.selectedItem
    },
    selectedItemForeignKeyName () {
      if (!this.selectedItem || !this.archiveFileTemplate || !this.currentModelTemplate) return null
      const referencingForeignKey = Object.values(this.archiveFileTemplate.relations).find(item => item.foreignTable === this.currentModelTemplate.table)
      return referencingForeignKey ? referencingForeignKey.localColumns[0] : null
    },
    selectedItemIdFilterName () {
      if (!this.selectedItem || !this.filesFormTemplate || !this.currentModelTemplate) return null
      return Object.keys(this.filesFormTemplate.filterDataModels)
        .find(key => this.filesFormTemplate.filterDataModels[key].model === this.dataModel)
    },
    filesFilterQuery () {
      if (!this.selectedItem || !this.archiveFileTemplate) return null
      const filterQuery = {}
      const archiveFileColumns = Object.keys(this.archiveFileTemplate.columns)
      Object.keys(this.filterDataModels)
        .map(key => {
          if (archiveFileColumns.includes(this.filterDataModels[key].ref)) {
            if (typeof this.selectedItem[this.filterDataModels[key].ref] === 'object') {
              filterQuery[key] = this.selectedItem[this.filterDataModels[key].ref].id
            } else {
              filterQuery[key] = this.selectedItem[this.filterDataModels[key].ref]
            }
          }
        })
      return Object.assign(filterQuery, { [this.selectedItemIdFilterName]: this.selectedItem.id })
    },
    filesFilterValues () {
      if (!this.selectedItem || !this.archiveFileTemplate) return {}
      const archiveFileColumns = Object.keys(this.archiveFileTemplate.columns)
      var filterValues = {}
      if (archiveFileColumns.includes(this.selectedItemForeignKeyName)) {
        filterValues = Object.assign(filterValues, { [this.selectedItemForeignKeyName]: this.selectedItem.id })
      }
      // console.log('filterValues:', filterValues)
      return filterValues
    }
  },
  created () {
    this.$store.dispatch('templates/getModelTemplate', 'ArchiveFile')
    this.$store.dispatch('templates/getFormTemplate', 'files')
    this.$store.dispatch('templates/getModelTemplate', this.dataModel)
  },
  methods: {
    onShowFilesClick () {
      this.$emit('show-files-click', this.filesFilterQuery)
    },
    onUploadFilesClick () {
      this.$emit('upload-files-click', this.filesFilterQuery)
    }
  },
  watch: {
    selectedItem: {
      immediate: true,
      async handler () {
        if (!this.selectedItem) {
          this.relatedFilesCount = 0
        } else {
          this.filesService = new CrudService('ArchiveFile')
          const data = await this.filesService.getList({ page: -1, fields: 'id' }, this.filesFilterValues)
          this.relatedFilesCount = data.items.length
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
