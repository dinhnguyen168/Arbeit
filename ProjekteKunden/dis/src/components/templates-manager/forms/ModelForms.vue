<template>
    <v-container fluid grid-list-md class="c-model-forms">
        <v-progress-linear v-if="loading" indeterminate />
        <v-layout row wrap>
            <v-flex sm12 md6 lg4>
                <v-card height="100%" color="blue-grey" dark>
                    <v-card-title>
                        <h2>{{$route.params.modelFullName}}</h2>
                    </v-card-title>
                    <v-card-text>
                        These tiles display information about certain forms that are available in the sidebar. <br/>
                        Each tile here represents a form that is bound to {{$route.params.modelFullName}}.<br/>
                        As we say, all forms here share the same "data model".
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="green" :to="`/settings/templates-manager/forms/${$route.params.modelFullName}/new`" :disabled="$route.params.modelFullName === 'ArchiveFile'">
                            New
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-flex>
            <v-flex sm12 md6 lg4 v-for="form in modelForms" :key="form.name">
                <form-template-summary :form="form" />
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import FormTemplateSummary from '../FormTemplateSummary'
export default {
  name: 'ModelForms',
  components: { FormTemplateSummary },
  data () {
    return {
      loading: false
    }
  },
  async mounted () {
    this.loading = true
    try {
      await this.$store.dispatch('templates/refreshSummary')
    } catch (error) {
      this.$dialog.notify.warning(error.message, { timeout: 30000 })
      console.log(error)
    } finally {
      this.loading = false
    }
  },
  computed: {
    modelForms () {
      return this.$store.state.templates.summary.forms.filter(item => item.dataModel === this.$route.params.modelFullName)
    }
  }
}
</script>

<style scoped lang="stylus">

</style>
