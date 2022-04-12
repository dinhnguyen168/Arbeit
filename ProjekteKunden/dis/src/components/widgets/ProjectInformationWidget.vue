<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode" :extraSettingsProps="['projectName', 'projectDescription', 'projectImage', 'projectContact', 'imageWidth', 'imageAlignment', 'imageTextStack', 'websiteLink']">
        <v-layout class="c-project-information" :row="widget.extraSettings.imageTextStack !== 'row'" :column="widget.extraSettings.imageTextStack !== 'column'">
            <v-flex xs6 :style="{ 'text-align': widget.extraSettings.imageAlignment || 'center' }">
                <v-img
                        class="c-project-information__image"
                        v-if="widget.extraSettings.projectImage"
                        :src="widget.extraSettings.projectImage"
                        :style="{width: widget.extraSettings.imageWidth || '100%', height: 'auto', 'margin-left': widget.extraSettings.imageAlignment === 'left' ? 0 : 'auto', 'margin-right': widget.extraSettings.imageAlignment === 'right' ? 0 : 'auto' }"></v-img>
            </v-flex>
            <v-flex xs6>
                <div class="headline c-project-information__title" v-if="widget.extraSettings.projectName">
                    {{widget.extraSettings.projectName}}
                </div>
                <a class="c-project-information__website" target="_blank" v-if="widget.extraSettings.websiteLink" :href="widget.extraSettings.websiteLink">{{widget.extraSettings.websiteLink}}</a>
                <div class="body-2 c-project-information__description" v-if="widget.extraSettings.projectDescription" style="white-space: pre-wrap;">{{widget.extraSettings.projectDescription}}</div>
                <div class="body-2 c-project-information__contact" v-if="widget.extraSettings.projectContact" style="white-space: pre-wrap;">{{widget.extraSettings.projectContact}}</div>
            </v-flex>
        </v-layout>
        <v-tooltip bottom>
            <template v-slot:activator="{ on }">
                <div class="text-xs-right mt-2">
                    <span v-on="on">{{ $store.state.gitInfo.version }} ({{ $store.state.gitInfo.commitdate }})</span>
                </div>
            </template>
            <ul>
                <li>Version: {{ $store.state.gitInfo.version }}</li>
                <li>Commit: <a :href="`https://gitlab.informationsgesellschaft.com/dis/dis/commit/${ $store.state.gitInfo.commit }`" target="_blank">{{$store.state.gitInfo.commit.slice(0, 8)}}</a></li>
                <li>Branch: {{ $store.state.gitInfo.branch }}</li>
                <li>Commit Date: {{ $store.state.gitInfo.commitdate }}</li>
            </ul>
        </v-tooltip>
        <template v-slot:extraSettingsForm="{ extraSettingsFormModel }">
            <v-layout wrap>
                <v-flex xs12 md6>
                    <v-text-field label="Project Name" v-model="extraSettingsFormModel.projectName"></v-text-field>
                    <v-text-field label="Project Website Link" v-model="extraSettingsFormModel.websiteLink"></v-text-field>
                    <v-textarea label="Project Description" v-model="extraSettingsFormModel.projectDescription" auto-grow @keyup.native.enter.stop></v-textarea>
                    <v-textarea label="Project Contacts" v-model="extraSettingsFormModel.projectContact" auto-grow @keyup.native.enter.stop></v-textarea>
                </v-flex>
                <v-flex xs12 md6>
                    <div>
                        <v-img v-if="extraSettingsFormModel.projectImage" :src="extraSettingsFormModel.projectImage">
                            <v-btn icon @click="pickFile">
                                <v-icon>edit</v-icon>
                            </v-btn>
                            <v-btn icon @click="extraSettingsFormModel.projectImage = ''">
                                <v-icon>close</v-icon>
                            </v-btn>
                        </v-img>
                        <v-btn v-else icon @click="pickFile">
                            <v-icon>add_a_photo</v-icon>
                        </v-btn>
                        <input type="file" style="display: none" ref="image" accept="image/*" @change="onFilePicked" />
                    </div>
                    <v-select label="Image Width" v-model="extraSettingsFormModel.imageWidth" :items="['25%', '50%', '75%', '100%']"></v-select>
                    <v-select label="Image Alignment" v-model="extraSettingsFormModel.imageAlignment" :items="['left', 'center', 'right']"></v-select>
                    <v-select label="Stack Image and Text as" v-model="extraSettingsFormModel.imageTextStack" :items="['row', 'column']"></v-select>
                </v-flex>
            </v-layout>
        </template>
    </base-widget>
</template>

<script>
import BaseWidget from './BaseWidget'
export default {
  name: 'ProjectInformationWidget',
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
  methods: {
    pickFile () {
      this.$refs.image.click()
    },
    async onFilePicked (event) {
      const files = event.target.files
      if (files[0] !== undefined) {
        if (files[0].name.lastIndexOf('.') <= 0) {
          return
        }
        // const fr = new FileReader()
        // fr.readAsDataURL(files[0])
        // fr.addEventListener('load', () => {
        // })
        this.$refs.baseWidget.extraSettingsFormModel.projectImage = await this.resizeImage(files[0])
      } else {
        this.$refs.baseWidget.extraSettingsFormModel.projectImage = null
      }
    },
    resizeImage (imageFile, maxWidth = 1920, maxHeight = 1080) {
      console.log(imageFile)
      return new Promise((resolve, reject) => {
        let image = new Image()
        image.src = URL.createObjectURL(imageFile)
        image.onload = () => {
          const canvas = document.createElement('canvas')
          const ctx = canvas.getContext('2d')
          const scale = Math.min((maxWidth / image.width), (maxHeight / image.height))
          canvas.width = image.width * scale
          canvas.height = image.height * scale
          ctx.drawImage(image, 0, 0, canvas.width, canvas.height)
          resolve(canvas.toDataURL())
        }
      })
    }
  }
}
</script>
