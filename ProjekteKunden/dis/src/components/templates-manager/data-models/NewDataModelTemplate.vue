<template>
    <v-container fluid grid-list-md>
        <v-progress-linear v-if="loading" indeterminate />
        <data-model-template :initialTemplate="template" scenario="create"/>
    </v-container>
</template>

<script>
import DataModelTemplate from './DataModelTemplate'
import upperFirst from 'lodash/upperFirst'
export default {
  name: 'NewDataModelTemplate',
  components: { DataModelTemplate },
  data () {
    return {
      loading: false,
      template: {
        module: upperFirst(this.$route.params.moduleName),
        name: '',
        parentModel: '',
        columns: [
          {
            name: 'id',
            importSource: '',
            type: 'integer',
            size: 11,
            required: false,
            primaryKey: true,
            autoInc: true,
            label: 'ID',
            description: '',
            validator: '',
            validatorMessage: '',
            unit: '',
            selectListName: '',
            calculate: '',
            defaultValue: ''
          }
        ],
        indices: [
          {
            name: 'pk_id',
            type: 'PRIMARY',
            columns: [
              'id'
            ]
          }
        ],
        relations: [],
        behaviors: []
      },
      serverValidationErrors: []
    }
  },
  async created () {
    try {
      this.loading = true
      await this.$store.dispatch('templates/refreshSummary')
      await this.$store.dispatch('templates/refreshBehaviors')
    } catch (error) {
      this.$dialog.notify.warning(error.message, { timeout: 30000 })
      console.log(error)
    } finally {
      this.loading = false
    }
  },
  mounted () {
    this.$setTitle('Create Data Model Template')
  }
}
</script>

<style scoped>

</style>
