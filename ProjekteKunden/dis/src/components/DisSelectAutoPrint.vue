<template>
    <v-layout align-center :class="Object.assign({'c-dis-select-auto-print': true}, themeClasses)">
      <v-flex shrink>
        <v-checkbox class="mt-0" hide-details v-model="value.active" label="Print on save" :disabled="!value.reportName"></v-checkbox>
      </v-flex>
      <v-flex shrink>
        <v-menu v-model="showReportList" offset-y>
          <template v-slot:activator="{ on, attrs }">
            <v-btn
              icon
              v-bind="attrs"
              v-on="on"
              v-el-mousetrap="{keys: 'down'}"
            >
              <v-icon>expand_more</v-icon>
            </v-btn>
          </template>
          <v-card>
            <v-card-text class="pb-0">
              <v-radio-group v-model="value.reportName" class="mt-0">
                <v-radio
                  v-for="report in autoPrintReports"
                  :key="report.name"
                  :label="report.title"
                  :value="report.name"
                ></v-radio>
              </v-radio-group>
            </v-card-text>
          </v-card>
        </v-menu>
      </v-flex>
    </v-layout>
</template>

<script>
import themeable from 'vuetify/lib/mixins/themeable'

export default {
  name: 'DisSelectAutoPrint',
  mixins: [
    themeable
  ],
  props: {
    autoPrintReports: {
      type: Array,
      required: true
    },
    /**
     * v-model-Object must be of structure { active: <boolean>, reportName: <string> }
     */
    value: {
      type: Object,
      required: true
    }
  },
  data () {
    return {
      showReportList: false
    }
  },
  created () {
    if (!this.value.reportName) {
      this.value.active = false
    }
  },
  beforeDestroy () {
  },
  async mounted () {
  },
  computed: {
  },
  methods: {
    onSelectReport (item, e) {
      console.log('item:', item, 'e:', e)
      this.value.reportName = item.name
    }
  },
  watch: {
  }
}
</script>
