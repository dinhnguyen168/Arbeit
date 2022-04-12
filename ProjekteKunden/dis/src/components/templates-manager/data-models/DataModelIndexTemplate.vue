<template>
    <div :class="Object.assign({'c-index-template': true}, themeClasses)">
        <dis-simple-panel>
            <template v-slot:head>
                <h3 >{{value.name}}</h3>
                <v-tooltip right>
                  <template v-slot:activator="{ on, attrs }">
                    <v-icon v-if="value.isLocked || value.name === 'id'"
                            class="ml-2"
                            color="grey darken-1"
                            v-bind="attrs"
                            v-on="on"
                    >lock_outline</v-icon>
                    <v-icon v-if="value.type === 'one_to_many' || value.type === 'many_to_many'"
                            class="ml-2"
                            color="grey darken-1"
                            v-bind="attrs"
                            v-on="on"
                    >all_inclusive</v-icon>
                    <v-icon v-if="value.name === parentModelColumnName"
                            class="ml-2"
                            color="grey darken-1"
                            v-bind="attrs"
                            v-on="on"
                    >scatter_plot</v-icon>
                  </template>
                  <span v-if="value.isLocked || value.name === 'id'">Can not be modified</span>
                  <span v-if="value.type === 'one_to_many' || value.type === 'many_to_many'">
                      Part of a relation
                    </span>
                  <span v-if="value.name === parentModelColumnName">
                      Part of the parent relation
                    </span>
                </v-tooltip>
            </template>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-btn @click="$emit('remove')" color="error" :disabled="value.isLocked || value.type === 'PRIMARY'">Remove Index</v-btn>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-select :items="['PRIMARY', 'UNIQUE', 'KEY']" label="Type" v-model="value.type"  :disabled="value.isLocked || value.type === 'PRIMARY'"/>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-select label="Columns" v-model="value.columns" :items="columns" multiple chips :disabled="value.isLocked || value.type === 'PRIMARY'"/>
                    </v-card-text>
                </v-card>
            </v-flex>
        </dis-simple-panel>
    </div>
</template>
<script>
import themeable from 'vuetify/lib/mixins/themeable'
export default {
  name: 'DataModelIndexTemplate',
  mixins: [themeable],
  props: {
    value: {
      type: Object,
      required: true
    },
    columns: {
      type: Array,
      required: true
    },
    parentModelColumnName: {
      type: String
    }
  },
  data () {
    return {
    }
  }
}
</script>
