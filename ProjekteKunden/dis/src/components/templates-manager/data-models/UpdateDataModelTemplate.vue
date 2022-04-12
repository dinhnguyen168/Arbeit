<template>
    <v-container fluid grid-list-md>
        <v-progress-linear v-if="loading" indeterminate />
        <data-model-template v-if="template" :initialTemplate="template" scenario="update"/>
    </v-container>
</template>

<script>
import DataModelTemplate from '@/components/templates-manager/data-models/DataModelTemplate'
export default {
  name: 'UpdateDataModelTemplate',
  components: { DataModelTemplate },
  data () {
    return {
      loading: false,
      template: null
    }
  },
  async created () {
    this.loading = true
    try {
      await this.$store.dispatch('templates/refreshSummary')
      await this.$store.dispatch('templates/refreshBehaviors')
      let template = await this.$store.dispatch('templates/getModelTemplate', this.$route.params.modelFullName)
      let model = {
        module: template.module,
        name: template.name,
        parentModel: template.parentModel,
        columns: Object.values(template.columns),
        indices: Object.values(template.indices),
        relations: Object.values(template.relations),
        behaviors: template.behaviors || []
      }
      this.template = model
    } catch (error) {
      await this.$dialog.notify.warning(error.message, { timeout: 30000 })
      console.log(error)
    } finally {
      this.loading = false
    }
  },
  async mounted () {
    this.$setTitle('Update Data Model Template')
  }
}
</script>

<style scoped>

</style>
