<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode" :extraSettingsProps="['videoTutorialsLink', 'docsPdf', 'userHelpLink', 'dataDescriptionPdf']">
        <v-list>
            <v-list-tile :href="widget.extraSettings.videoTutorialsLink" v-if="widget.extraSettings.videoTutorialsLink" target="_blank">
                <v-list-tile-action>
                    <v-icon color="red">video_library</v-icon>
                </v-list-tile-action>
                <v-list-tile-content>
                    <v-list-tile-title>
                        Video Tutorials
                    </v-list-tile-title>
                </v-list-tile-content>
            </v-list-tile>
            <v-list-tile :href="widget.extraSettings.docsPdf" v-if="widget.extraSettings.docsPdf" target="_blank">
                <v-list-tile-action>
                    <v-icon>insert_drive_file</v-icon>
                </v-list-tile-action>
                <v-list-tile-content>
                    <v-list-tile-title>
                        PDF Documentation
                    </v-list-tile-title>
                </v-list-tile-content>
            </v-list-tile>
            <v-list-tile :href="widget.extraSettings.userHelpLink" v-if="widget.extraSettings.userHelpLink" target="_blank">
                <v-list-tile-action>
                    <v-icon color="blue">help</v-icon>
                </v-list-tile-action>
                <v-list-tile-content>
                    <v-list-tile-title>
                        User Help
                    </v-list-tile-title>
                </v-list-tile-content>
            </v-list-tile>
            <v-list-tile :href="widget.extraSettings.dataDescriptionPdf" v-if="widget.extraSettings.dataDescriptionPdf" target="_blank">
                <v-list-tile-action>
                    <v-icon>insert_drive_file</v-icon>
                </v-list-tile-action>
                <v-list-tile-content>
                    <v-list-tile-title>
                        Data Description
                    </v-list-tile-title>
                </v-list-tile-content>
            </v-list-tile>
        </v-list>
        <template v-slot:extraSettingsForm="{ extraSettingsFormModel }">
            <v-layout wrap>
                <v-flex xs12 md6>
                    <v-text-field label="Video Tutorials Link" v-model="extraSettingsFormModel.videoTutorialsLink"></v-text-field>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field label="Docs Pdf File" v-model="extraSettingsFormModel.docsPdf"></v-text-field>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field label="User Help Link" v-model="extraSettingsFormModel.userHelpLink"></v-text-field>
                </v-flex>
                <v-flex xs12 md6>
                    <v-text-field label="Data Description PDF File" v-model="extraSettingsFormModel.dataDescriptionPdf"></v-text-field>
                </v-flex>
            </v-layout>
        </template>
    </base-widget>
</template>

<script>
import BaseWidget from './BaseWidget'
export default {
  name: 'InstructionsWidget',
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
  }
}
</script>
