<template>
    <v-card flat height="400" class="scroll-y">
        <v-card-text>
            <v-menu :disabled="!!this.updateFormModel" v-model="datePickerMenu" :close-on-content-click="false" :nudge-right="40" transition="scale-transition" offset-y full-width max-width="290px" min-width="290px">
                <template v-slot:activator="{ on }">
                    <v-text-field
                            v-model="computedDateFormatted"
                            label="Date"
                            readonly
                            prepend-icon="event"
                            v-on="on"
                    ></v-text-field>
                </template>
                <v-date-picker v-model="formModel.date" no-title @input="datePickerMenu = false" @change="onDateChange"></v-date-picker>
            </v-menu>
            <v-select label="File Type" v-model="fileType" :items="fileTypesList" />
            <v-textarea rows="2" auto-grow label="Message Of The Day" hide-details outline v-model="formModel.message"></v-textarea>
            <v-progress-linear indeterminate v-if="isLoadingImages"></v-progress-linear>
            <v-layout v-else row v-for="(image, index) in imagesList" :key="image.id" align-center>
                <v-flex>
                    <v-img :src="image.image_id ? baseUrl + `files/${image.image_id}` : image.src" />
                </v-flex>
                <v-flex>
                    <v-checkbox hide-details v-model="formModel.images" :value="imagesList[index]" label="Add to message?" />
                    <v-textarea rows="2" auto-grow label="Caption" hide-details outline v-model="imagesList[index].caption"></v-textarea>
                </v-flex>
            </v-layout>
            <v-alert v-model="showErrorSummary" dismissible>
                <ul>
                    <li v-for="error in serverValidationErrors" :key="error.field">
                        <strong>{{error.field}}</strong>: {{error.message}}
                    </li>
                </ul>
            </v-alert>
        </v-card-text>
        <v-card-actions class="justify-end">
            <slot name="extra-buttons"></slot>
            <v-btn @click="submitMessage" :loading="isPosting" color="primary">
                Submit
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import MessageOfTheDayService from '../../../services/MessageOfTheDayService'
import CrudService from '../../../services/CrudService'
import ListValuesService from '../../../services/ListValuesService'

const messageOfTheDayService = new MessageOfTheDayService()
export default {
  name: 'MessageOfTheDayForm',
  props: {
    messageDefaultImage: {
      type: Object,
      required: true
    },
    updateFormModel: {
      type: Object,
      required: false
    }
  },
  data () {
    return {
      formModel: {
        id: null,
        date: new Date().toISOString().substr(0, 10),
        message: '',
        images: []
      },
      imagesList: [],
      dateFormatted: this.formatDate(new Date().toISOString().substr(0, 10)),
      datePickerMenu: false,
      isPosting: false,
      isLoadingImages: false,
      serverValidationErrors: [],
      fileType: null,
      fileTypesList: [],
      baseUrl: window.baseUrl
    }
  },
  computed: {
    showErrorSummary: {
      get: function () {
        return this.serverValidationErrors.length > 0
      },
      set: function (val) {
        !val && (this.serverValidationErrors = [])
      }
    },
    userCanDeleteMessage () {
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes(`sa`) || this.$store.state.loggedInUser.roles.includes(`developer`))
    },
    computedDateFormatted () {
      return this.formatDate(this.formModel.date)
    }
  },
  mounted () {
    this.getFileTypesList()
    if (this.updateFormModel) {
      this.formModel = Object.assign({}, this.updateFormModel)
      this.getFilesByDate()
    } else {
      this.getFilesByDate()
    }
  },
  methods: {
    async submitMessage () {
      try {
        this.isPosting = true
        this.showErrorSummary = false
        if (!this.updateFormModel) {
          const data = await messageOfTheDayService.post({
            message: this.formModel.message,
            date: this.formModel.date,
            images: this.formModel.images
          })
          this.$emit('message-created', data)
          this.getFilesByDate(this.newMessageDate)
          this.formModel.id = null
          this.formModel.message = ''
          this.formModel.date = new Date().toISOString().substr(0, 10)
          this.formModel.images = []
        } else {
          const data = await messageOfTheDayService.put(this.formModel.id, this.formModel)
          this.$emit('message-updated', data)
        }
      } catch (error) {
        console.log(error)
        if (error.response && error.response.status === 422) {
          this.$dialog.notify.warning('Message is not valid.')
          this.serverValidationErrors = error.response.data
        } else {
          this.$dialog.notify.warning(error.message)
        }
      } finally {
        this.isPosting = false
      }
    },
    formatDate (date) {
      if (!date) return null

      const [year, month, day] = date.split('-')
      return `${day}-${month}-${year}`
    },
    parseDate (date) {
      if (!date) return null
      const [month, day, year] = date.split('-')
      return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
    },
    onDateChange (newDate) {
      console.log('onDateChange', newDate)
      this.getFilesByDate()
    },
    async getFilesByDate () {
      if (!this.fileType) {
        return []
      }
      try {
        this.isLoadingImages = true
        this.imagesList = []
        this.formModel.images = this.updateFormModel ? this.updateFormModel.images : []
        const crudService = new CrudService('ArchiveFile')
        const data = await crudService.getList({ 'filter[upload_date]': this.formModel.date, 'filter[mime_type]': 'image/', 'filter[type]': this.fileType })
        for (let i = 0; i < data.items.length; i++) {
          if (this.updateFormModel && this.updateFormModel.images.findIndex(item => item.image_id === data.items[i].id) > -1) {
            // merge message images into the images list options
            this.imagesList.push(this.updateFormModel.images.find(item => item.image_id === data.items[i].id))
          } else {
            this.imagesList.push({
              image_id: data.items[i].id,
              caption: data.items[i].remarks
            })
          }
        }
        // add default image if there were no images attached at the selected date
        if (this.imagesList.length === 0) {
          this.imagesList.push(this.messageDefaultImage)
        }
      } catch (error) {
        this.$dialog.notify.warning(error.message)
      } finally {
        this.isLoadingImages = false
      }
    },
    async getFileTypesList () {
      const service = new ListValuesService('ListValues')
      const data = await service.getList({ 'sort': 'sort', 'fields': 'remark,display,sort' }, { 'listname': 'UPLOAD_FILE_TYPE' })
      this.fileTypesList = data.items.map(item => {
        return {
          text: item.display + ' | ' + item.remark,
          value: item.display
        }
      })
    }
  },
  watch: {
    newMessageDate (val) {
      this.dateFormatted = this.formatDate(this.newMessageText)
    },
    fileType (newValue) {
      this.getFilesByDate()
    }
  }
}
</script>

<style scoped>

</style>
