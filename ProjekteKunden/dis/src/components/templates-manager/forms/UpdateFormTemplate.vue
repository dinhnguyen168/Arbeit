<template>
    <div>
        <v-progress-linear v-if="loading" indeterminate />
        <form-template v-if="ready" :availableFieldsTemplates="availableFieldsTemplates" :initialTemplate="initialTemplate" scenario="update"/>
    </div>
</template>

<script>
import FormTemplate from './FormTemplate'
import DisTemplateService from '../../../services/DisTemplateService'
export default {
  name: 'UpdateFormTemplate',
  components: { FormTemplate },
  data () {
    return {
      loading: false,
      availableFieldsTemplates: {},
      initialTemplate: [],
      ready: false
    }
  },
  async created () {
    try {
      this.loading = true
      this.$setTitle('Update Form Template')
      await this.$store.dispatch('templates/getFormTemplate', this.$route.params.formName)
      const formTemplate = this.$store.state.templates.forms.find(item => item.name === this.$route.params.formName)
      const oldTemplateFieldsAsObject = formTemplate.fields.reduce((acc, cur, i) => {
        acc[cur.name] = cur
        return acc
      }, {})
      let service = new DisTemplateService()
      let response = await service.getFormTemplateSeed(formTemplate.dataModel)
      this.availableFieldsTemplates = Object.assign(response.data.availableFieldTemplates, oldTemplateFieldsAsObject)
      this.initialTemplate = JSON.parse(JSON.stringify(formTemplate))
      this.ready = true
    } catch (error) {
      this.$dialog.notify.warning(error.message, { timeout: 30000 })
      console.log(error)
    } finally {
      this.loading = false
    }
  }
}
</script>

<style scoped>

</style>
