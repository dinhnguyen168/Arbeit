<template>
    <div class="c-upload">
        <input ref="fileInput" type="file" :multiple="multiple" @change="handleFileInputChange" />
        <div
                  :class="{ 'c-upload__dropzone': true, 'c-upload__dropzone--highlighted': isDropzoneHighlighted }"
                  ref="dropzone"
                  @dragover.prevent.stop="isDropzoneHighlighted = true"
                  @dragenter.prevent.stop="isDropzoneHighlighted = true"
                  @dragleave.prevent.stop="isDropzoneHighlighted = false"
                  @drop.prevent.stop="handleFileDrop"
                  @click="$refs.fileInput.click()">
            <v-flex
                    shrink
                    v-for="(fileToUpload, key) in filesToUpload"
                    :key="key" class="c-upload__file-preview">
                <div class="c-upload__icon">
                    <v-icon>image</v-icon>
                </div>
                <div class="c-upload__file-name" :title="fileToUpload.file.name">
                    <v-progress-circular size="15" width="2" v-model="fileToUpload.progress" color="primary" :indeterminate="fileToUpload.progress === 0"></v-progress-circular> {{ fileToUpload.file.name }}
                </div>
            </v-flex>
        </div>
    </div>
</template>

<script>
import FileService from '../services/FileService'
// import Uppie from 'uppie'
// const uppie = new Uppie()
export default {
  name: 'DisFileUpload',
  props: {
    appendForm: {
      type: Object,
      default: () => { return {} }
    },
    multiple: {
      type: Boolean,
      default: true
    },
    allowedTypes: {
      type: Array
    },
    uploadPath: {
      type: String,
      default: ''
    }
  },
  data () {
    return {
      filesToUpload: [],
      isDropzoneHighlighted: false,
      isUploading: false,
      progress: 0
    }
  },
  created () {
    this.service = new FileService()
  },
  mounted () {
  },
  methods: {
    async handleFileInputChange (e) {
      ([...this.$refs.fileInput.files]).forEach(fileToUpload => {
        if (this.filesToUpload.findIndex(item => item.file.name === fileToUpload.name) < 0) {
          this.filesToUpload.push({
            file: fileToUpload,
            progress: 0
          })
        }
      })
      this.$refs.fileInput.value = null
    },
    handleFileDrop (e) {
      this.isDropzoneHighlighted = false
      let dt = e.dataTransfer
      if (dt.files) {
        if (!this.multiple && dt.files.length > 1) {
          this.$dialog.message.error('multiple file upload is not allowed')
          return false
        }
        ([...dt.files]).forEach(fileToUpload => {
          if (this.filesToUpload.findIndex(item => item.file.name === fileToUpload.name) < 0) {
            if (!this.allowedTypes || (this.allowedTypes && this.allowedTypes.includes(fileToUpload.type))) {
              this.filesToUpload.push({
                file: fileToUpload,
                progress: 0
              })
            } else {
              this.$dialog.message.error(`only ${this.allowedTypes.join(', ')} file types are allowed`)
            }
          }
        })
      }
    },
    removeFile (key) {
      this.filesToUpload.splice(key, 1)
    },
    async upload () {
      if (this.filesToUpload.length === 0) return
      this.isUploading = true
      let file = this.filesToUpload[0].file
      let formData = new FormData()
      formData.append('FilesUploadNewFormModel[files][0]', file)
      formData.append('FilesUploadNewFormModel', 'true')
      formData.append('FilesUploadNewFormModel[uploadPath]', this.uploadPath)
      try {
        let newFile = await this.service.upload(formData, (progress) => this.updateProgress(0, progress))
        this.filesToUpload.splice(0, 1)
        this.$emit('uploaded', newFile)
      } catch (e) {
        this.$dialog.notify.warning('error while uploading ' + file.name)
        console.log(e)
      } finally {
        this.isUploading = false
      }
    },
    updateProgress (fileIndex, percentage) {
      this.filesToUpload[fileIndex].progress = percentage
    }
  },
  watch: {
    filesToUpload () {
      if (!this.isUploading) {
        this.upload()
      }
    }
  }
}
</script>

<style scoped lang="scss">
    .c-upload {
        overflow: hidden;
        position: relative;
        input {
            visibility: hidden;
            position: absolute;
            top: -600px;
        }
        &__dropzone {
            border: dashed 2px rgb(0, 216, 234);
            background-color: rgba(0, 0, 0, 0.17);
            border-radius: 10px;
            cursor: pointer;
            padding: 10px;
            min-height: 100px;
            display: flex;
            &--highlighted {
                background-color: rgba(0, 0, 0, 0.3);
            }
        }
        &__file-preview {
            margin: 5px;
            padding: 5px;
            width: 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.3)
        }
        &__icon {
            flex: auto;
        }
        &__file-name {
            width: 100%;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }
    }
</style>
