<template>
    <div :class="Object.assign({'c-index-template': true}, themeClasses)">
        <dis-simple-panel>
            <template v-slot:head>
                <v-icon class="drag-handle mr-3">drag_indicator</v-icon>
                <h3 >{{behaviorName}}</h3>
            </template>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-btn @click="$emit('remove')" color="error">Remove Behavior</v-btn>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg8>
                <v-card>
                    <v-card-text>
                        <v-layout row wrap>
                            <v-flex pa-2 sm12 lg6 v-for="param in behaviorInfo.parameters" :key="param.name">
                                <v-text-field v-model="value.parameters[param.name]" persistent-hint :hint="behaviorHints[param.name]" :label="param.name"></v-text-field>
                            </v-flex>
                        </v-layout>
                    </v-card-text>
                </v-card>
            </v-flex>
        </dis-simple-panel>
    </div>
</template>
<script>
import themeable from 'vuetify/lib/mixins/themeable'
export default {
  name: 'DataModelBehaviorTemplate',
  mixins: [themeable],
  props: {
    value: {
      type: Object,
      required: true
    }
  },
  data () {
    return {}
  },
  computed: {
    behaviorInfo () {
      return this.$store.state.templates.behaviors.find(item => item.behaviorClass === this.value.behaviorClass)
    },
    behaviorName () {
      return this.behaviorInfo ? this.behaviorInfo.name : null
    },
    behaviorHints () {
      if (!this.behaviorInfo) {
        return null
      }
      let hints = {}
      this.behaviorInfo.parameters.forEach(item => {
        hints[item.name] = item.hint
      })
      return hints
    }
  },
  watch: {}
}
</script>
