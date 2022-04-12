<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode" :extraSettingsProps="['calendarWidth']">
        <v-layout row class="messages">
            <v-flex :class="widget.extraSettings.calendarWidth || 'xs8'">
                <v-layout row justify-space-between>
                    <v-btn fab icon outline small color="primary" @click="$refs.calendar.prev()">
                        <v-icon dark>
                            keyboard_arrow_left
                        </v-icon>
                    </v-btn>
                    <v-progress-circular v-if="isLoading" indeterminate></v-progress-circular>
                    <v-btn fab icon outline small color="primary" @click="$refs.calendar.next()">
                        <v-icon dark>
                            keyboard_arrow_right
                        </v-icon>
                    </v-btn>
                </v-layout>
                <v-sheet height="400">
                    <v-calendar v-model="start" :start="start" :end="end" ref="calendar" color="primary" @change="onCalendarChange">
                        <template v-slot:day="{ date }">
                            <template v-for="message in messages.filter(item => item.date === date)">
                                <div v-ripple class="blue text--white pa-1 message-in-calendar" v-html="message.message" :key="message.id" @click="selectedMessage = message" v-bind:title="message.message.trim()"></div>
                            </template>
                        </template>
                    </v-calendar>
                </v-sheet>
            </v-flex>
            <v-flex>
                <v-tabs v-model="activeTab" dark>
                    <v-tab key="last-message" ripple>
                        Last Message
                    </v-tab>
                    <v-tab key="new-message" ripple>
                        New Message
                    </v-tab>
                    <v-tab-item key="last-message">
                        <message-of-the-day-view
                                :messageDefaultImage="messageDefaultImage"
                                :previewMessage="previewMessage"
                                :isLastMessage="!selectedMessage || (selectedMessage && lastMessage && selectedMessage.id === lastMessage.id)"
                                @show-last-message="selectedMessage = null"
                                @delete-message="onDeleteMessage"
                                @message-updated="onMessageUpdated"></message-of-the-day-view>
                    </v-tab-item>
                    <v-tab-item key="new-message">
                        <message-of-the-day-form :messageDefaultImage="messageDefaultImage" @message-created="onMessageCreated"></message-of-the-day-form>
                    </v-tab-item>
                </v-tabs>
            </v-flex>
        </v-layout>
        <template v-slot:extraSettingsForm="{ extraSettingsFormModel }">
            <v-layout wrap>
                <v-flex xs12>
                    <v-select label="Calendar width" v-model="extraSettingsFormModel.calendarWidth" :items="[{ text: '25%', value: 'xs3' }, { text: '33%', value: 'xs4' }, { text: '50%', value: 'xs6' }, { text: '75%', value: 'xs9' }]">
                    </v-select>
                </v-flex>
            </v-layout>
        </template>
    </base-widget>
</template>

<script>
import BaseWidget from '../BaseWidget'
import MessageOfTheDayService from '../../../services/MessageOfTheDayService'
import MessageOfTheDayForm from './MessageOfTheDayForm'
import MessageOfTheDayView from './MessageOfTheDayView'

const messageOfTheDayService = new MessageOfTheDayService()
export default {
  name: 'MessageOfTheDayWidget',
  components: { MessageOfTheDayView, MessageOfTheDayForm, BaseWidget },
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
      messages: [],
      messagesMeta: {
        currentPage: 1,
        pageCount: 2,
        perPage: 5,
        totalCount: 7
      },
      isLoading: false,
      lastMessage: null,
      selectedMessage: null,
      activeTab: null,
      start: null,
      end: null,
      messageDefaultImage: {
        image_id: 0,
        src: require('../../../assets/logo.png'),
        caption: 'Default image: woodpecker logo'
      }
    }
  },
  mounted () {
    this.getLastMessage()
  },
  computed: {
    messagesMap () {
      // unused ?
      const map = {}
      this.messages.forEach(item => (map[item.date] = map[item.date] || []).push(item))
      return map
    },
    previewMessage () {
      return this.selectedMessage || this.lastMessage
    }
  },
  methods: {
    async getMessages (start, end) {
      try {
        this.isLoading = true
        const data = await messageOfTheDayService.get({
          'filter[date][gt]': start,
          'filter[date][lt]': end
        })
        this.messages = data.items
        this.messagesMeta = data._meta
      } catch (error) {
        console.log(error)
      } finally {
        this.isLoading = false
      }
    },
    async getLastMessage () {
      try {
        this.isLoading = true
        const data = await messageOfTheDayService.get({
          'per-page': 1,
          'sort': '-date'
        })
        if (data.items.length) {
          this.lastMessage = data.items[0]
        } else {
          this.lastMessage = null
        }
      } catch (error) {
        console.log(error)
      } finally {
        this.isLoading = false
      }
    },
    async onDeleteMessage (messageId) {
      try {
        this.isLoading = true
        const confirm = await this.$dialog.confirm({
          title: 'Delete a Message',
          text: 'This action cannot be reverted. Are you sure?'
        })
        if (confirm) {
          await messageOfTheDayService.delete(messageId)
          this.selectedMessage = null
          this.messages = this.messages.filter(item => item.id !== messageId)
          this.getLastMessage()
        }
      } catch (error) {
        console.log(error)
        this.$dialog.notify.warning(error.message)
      } finally {
        this.isLoading = false
      }
    },
    onCalendarChange (event) {
      this.getMessages(event.start.date, event.end.date)
    },
    onMessageCreated (newMessage) {
      this.messages.push(newMessage)
      if (!this.lastMessage || (this.lastMessage && newMessage.date >= this.lastMessage.date)) {
        this.lastMessage = newMessage
      } else {
        this.getLastMessage()
      }
    },
    async onMessageUpdated (updatedMessage) {
      console.log('onMessageUpdated')
      const i = this.messages.findIndex(item => item.id === updatedMessage.id)
      this.messages[i].message = updatedMessage.message
      this.messages[i].images = updatedMessage.images
      await this.$nextTick()
      this.selectedMessage = this.messages[i]
    }
  }
}
</script>

<style>
    .message-in-calendar {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin: 2px 0;
    }
    .image-caption {
        background: rgba(0, 0, 0, 0.5);
    }
</style>
