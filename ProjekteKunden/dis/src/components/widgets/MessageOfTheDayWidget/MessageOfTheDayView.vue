<template>
    <v-card flat height="400" class="scroll-y">
        <v-card-text v-if="previewMessage">
            <v-layout row align-center>
                <span class="title">{{previewMessage.date}}</span>
                <v-spacer></v-spacer>
                <v-btn flat small @click="() => $emit('show-last-message')" :disabled="isLastMessage">Show Last Message</v-btn>
            </v-layout>
            <div class="body-1 mt-1 mb-3">
                {{previewMessage.message}}
            </div>
                <v-img v-for="image in previewMessage.images" :key="image.id" :src="baseUrl + `files/${image.image_id}`" :aspect-ratio="1" class="mb-1" :title="`${previewMessage.date}: ${previewMessage.message.trim()} (${image.caption.trim()})`">
                    <v-container fill-height fluid pa-0>
                        <v-layout row align-end justify-center fill-height>
                            <v-flex pa-2 class="image-caption">
                                <span class="body-2">{{previewMessage.date}}: {{image.caption}}</span>
                            </v-flex>
                        </v-layout>
                    </v-container>
                    <template v-slot:placeholder>
                        <v-layout fill-height align-center justify-center ma-0>
                            <v-progress-circular indeterminate color="grey lighten-5"></v-progress-circular>
                        </v-layout>
                    </template>
                </v-img>
                <v-img v-if="previewMessage.images.length === 0" :src="messageDefaultImage.src" :aspect-ratio="1" class="mb-1" :title="`${previewMessage.date}: ${previewMessage.message.trim()} (${messageDefaultImage.caption.trim()})`">
                    <v-container fill-height fluid pa-0>
                        <v-layout row align-end justify-center fill-height>
                            <v-flex pa-2 class="image-caption">
                                <span class="body-2">{{previewMessage.date}}: {{messageDefaultImage.caption}}</span>
                            </v-flex>
                        </v-layout>
                    </v-container>
                    <template v-slot:placeholder>
                        <v-layout fill-height align-center justify-center ma-0>
                            <v-progress-circular indeterminate color="grey lighten-5"></v-progress-circular>
                        </v-layout>
                    </template>
                </v-img>
            <div class="text-xs-right">
                <v-dialog v-model="updateModelDialog" max-width="600px" lazy>
                    <template v-slot:activator="{ on }">
                        <v-btn flat v-on="on">
                            <v-icon color="orange">edit</v-icon> edit message
                        </v-btn>
                    </template>
                    <message-of-the-day-form
                            v-if="updateModelDialog"
                            :update-form-model="previewMessage"
                            :messageDefaultImage="messageDefaultImage"
                            @message-updated="onMessageUpdated">
                        <template v-slot:extra-buttons>
                            <v-btn color="red" dark @click="updateModelDialog = false"> Close</v-btn>
                        </template>
                    </message-of-the-day-form>
                </v-dialog>
                <v-btn flat @click="() => $emit('delete-message', previewMessage.id)">
                    <v-icon color="red">delete</v-icon> delete message
                </v-btn>
            </div>
        </v-card-text>
        <v-card-text v-else>
            No messages were written yet. Use the form to create a new one
        </v-card-text>
    </v-card>
</template>

<script>
import MessageOfTheDayForm from './MessageOfTheDayForm'
export default {
  name: 'MessageOfTheDayView',
  components: { MessageOfTheDayForm },
  props: {
    previewMessage: {
      required: true
    },
    messageDefaultImage: {
      type: Object,
      required: true
    },
    isLastMessage: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      updateModelDialog: false,
      baseUrl: window.baseUrl
    }
  },
  methods: {
    onMessageUpdated (message) {
      this.$emit('message-updated', message)
      this.updateModelDialog = false
    }
  }
}
</script>

<style scoped>

</style>
