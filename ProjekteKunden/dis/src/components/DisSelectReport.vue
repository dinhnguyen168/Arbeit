<template>
    <v-menu v-model="showMenu" :close-on-content-click="false" offset-y class="c-dis-select-report">
        <!-- <v-btn slot="activator" :disabled="!selectedItem" class="c-dis-form__btn-export" dark>Export <v-icon>expand_more</v-icon></v-btn> -->
        <template v-slot:activator="data">
            <v-btn
                v-on="data.on"
                :disabled="disabled"
                v-el-mousetrap="{keys: 'down', disabled: showMenu}"
                @mousetrap.prevent="showMenu = true"
                class="c-dis-select-report__btn-export" dark>Export <v-icon>expand_more</v-icon></v-btn>
        </template>
        <v-card width="400">
            <v-tabs v-model="activeTab">
                <v-tab v-for="tab in getTabs()" :key="tab.id" :disabled="tab.reports.length === 0">{{ tab.title }}</v-tab>
            </v-tabs>
            <v-tabs-items v-model="activeTab">
                <v-tab-item v-for="tab in getTabs()" :key="tab.id">
                    <v-card flat>
                        <v-card-text>
                            <v-list avatar>
                                <v-list-tile v-for="(item, index) in tab.reports" :key="index" @click="() => onSelectReport(item)">
                                    <v-list-tile-action>
                                        <v-icon v-if="item.type === 'report' && item.directPrint">print</v-icon>
                                        <v-icon v-else-if="item.type === 'report'">assignment</v-icon>
                                        <v-icon v-if="item.type === 'export'">assignment_returned</v-icon>
                                        <v-icon v-if="item.type === 'action'">flash_on</v-icon>
                                    </v-list-tile-action>
                                    <v-list-tile-title>{{ item.title }}</v-list-tile-title>
                                </v-list-tile>
                            </v-list>
                        </v-card-text>
                    </v-card>
                </v-tab-item>
            </v-tabs-items>
        </v-card>
    </v-menu>
</template>

<script>

export default {
  name: 'DisSelectReport',
  props: {
    reports: {
      type: Array,
      required: true
    },
    disabled: {
      type: Boolean
    }
  },
  data () {
    return {
      showMenu: false,
      activeTab: null
    }
  },
  created () {
  },
  beforeDestroy () {
  },
  async mounted () {
  },
  computed: {
    onlyReports () {
      return this.reports.filter(report => {
        return report.type === 'report'
      })
    },
    onlyExports () {
      return this.reports.filter(report => {
        return report.type === 'export'
      })
    },
    onlyActions () {
      return this.reports.filter(report => {
        return report.type === 'action'
      })
    }
  },
  methods: {
    onSelectReport (report) {
      this.showMenu = false
      this.$emit('select:report', report)
    },
    getTabs () {
      return [
        { id: 0, title: 'Reports', reports: this.onlyReports },
        { id: 1, title: 'Exports', reports: this.onlyExports },
        { id: 2, title: 'Actions', reports: this.onlyActions }
      ]
    }
  },
  watch: {
  }
}
</script>

<style scoped>
</style>
