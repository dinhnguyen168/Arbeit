<template>
    <v-container fluid grid-list-md class="pt-0">
        <v-layout row align-center>
            <v-flex grow>
                <v-progress-linear v-if="isLoading" indeterminate />
            </v-flex>
            <v-flex class="edit-mode" shrink v-if="$store.getters['userCanEditDashboard']">
                <v-switch label="edit mode" v-model="editMode"></v-switch>
            </v-flex>
        </v-layout>
        <draggable :force-fallback="true" v-model="myWidgets" v-bind="{group: 'widgets', handle: '.drag-handle', ghostClass: 'u-drag-ghost', animation: 200}" tag="div" @start="drag = true" @end="drag = false">
            <transition-group class="layout row wrap" type="transition" :name="!drag ? 'flip-list' : null">
                <widget v-for="widget in myWidgets" :key="widget.id" :widget="widget" :editMode="editMode"></widget>
            </transition-group>
        </draggable>
        <div v-if="editMode" class="inactive-widgets">
            <div class="title mt-4 mb-2">Inactive Widgets</div>
            <draggable :force-fallback="true" v-model="myInactiveWidgets" v-bind="{group: 'widgets', handle: '.drag-handle', ghostClass: 'u-drag-ghost', animation: 200}" tag="div" @start="drag = true" @end="drag = false">
                <transition-group class="layout row wrap" type="transition" :name="!drag ? 'flip-list' : null">
                    <base-widget v-for="widget in myInactiveWidgets" :key="widget.id" :widget="widget" :editMode="editMode"></base-widget>
                </transition-group>
            </draggable>
        </div>

        <v-dialog v-if="messages.length" v-model="showMessages" persistent width="400">
            <v-card >
                <v-card-title class="headline">Welcome</v-card-title>
                <v-card-text>
                    <v-alert v-for="(message, index) in messages" :key="index" :value="true" :type="message.type">{{ message.text }}</v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-spacer></v-spacer>
                    <v-btn @click="showMessages = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </v-container>
</template>

<script>
import ls from 'local-storage'
import Widget from '../components/widgets/Widget'
import BaseWidget from '../components/widgets/BaseWidget'
import draggable from 'vuedraggable'
import AppService from '../services/AppService'

export default {
  name: 'Dashboard',
  components: { BaseWidget, Widget, draggable },
  data () {
    return {
      isLoading: true,
      editMode: false,
      drag: false,
      messages: [],
      showMessages: true
    }
  },
  computed: {
    myWidgets: {
      get () {
        return this.$store.state.widgets.filter(item => item.active).sort((a, b) => a.order < b.order ? -1 : +1)
      },
      async set (value) {
        try {
          this.isLoading = true
          await this.$store.dispatch('reorderActiveWidgets', value)
        } catch (error) {
          this.$dialog.notify.warning('could not reorder widgets')
          await this.refreshWidgets()
          console.log(error)
        } finally {
          this.isLoading = false
        }
      }
    },
    myInactiveWidgets: {
      get () {
        return this.$store.state.widgets.filter(item => !item.active)
      },
      async set (value) {}
    }
  },
  created () {
    this.loadMessages()
  },
  mounted () {
    this.$setTitle('Dashboard')
    // this.$store.dispatch('templates/refreshSummary')
    this.refreshWidgets()
  },
  methods: {
    loadMessages () {
      // show Messages only once a day
      const localKey = 'lastMessDay'
      let today = (new Date()).getDay()
      console.log('today:', today, new Date())
      let lastDay = ls.get(window.baseUrl + localKey) || 0
      if (lastDay !== today) {
        let appService = new AppService()
        appService.getMessages().then((messages) => {
          this.messages = messages
          ls.set(window.baseUrl + localKey, today)
        })
      }
    },
    async refreshWidgets () {
      try {
        this.isLoading = true
        await this.$store.dispatch('getWidgets')
      } catch (error) {
        this.$dialog.notify.warning('could not load widgets')
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>

<style scoped>
.flip-list-move {
    transition: transform 0.5s;
}

.edit-mode {
    padding: 0 !important;
}

.edit-mode .v-input {
    margin:0;
    padding:0;
}

.edit-mode div.v-input__slot {
    margin-bottom: 0 !important;
}

.edit-mode .v-messages {
    display: none;
}

</style>
