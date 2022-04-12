<template>
    <div>
        <v-progress-linear v-if="loading" indeterminate />
        <form-template v-if="ready" :availableFieldsTemplates="availableFieldsTemplates" :initialTemplate="initialTemplate" scenario="create"/>
    </div>
</template>

<script>
import FormTemplate from './FormTemplate'
import DisTemplateService from '../../../services/DisTemplateService'
export default {
  name: 'NewFormTemplate',
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
    this.loading = true
    this.$setTitle('New Form Template')
    let service = new DisTemplateService()
    let response = await service.getFormTemplateSeed(this.$route.params.modelFullName)
    this.availableFieldsTemplates = response.data.availableFieldTemplates
    this.initialTemplate = response.data.initialTemplate
    this.ready = true
    this.loading = false
  }
}
</script>

<style scoped>

</style>
