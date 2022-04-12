<template>
    <!--<v-container class="pb-0 pt-0">-->
    <v-breadcrumbs :class="{'v-breadcrumbs-compact': this.$store.state.compactUI}" v-if="$route.name !== 'login'" :items="breadcrumbs"></v-breadcrumbs>
    <!--</v-container>-->
</template>

<script>
export default {
  name: 'DisBreadcrumbs',
  methods: {
    getBreadcrumb: function (route) {
      if (route.meta && route.meta.breadcrumb) {
        const bc = route.meta.breadcrumb
        return typeof bc === 'function' ? bc(this.$route.params) : bc
      }
      return route.name
    }
  },
  computed: {
    breadcrumbs: {
      get () {
        // https://github.com/Scrum/vue-2-breadcrumbs/blob/master/src/vue-2-breadcrumbs.js
        const breadcrumbs = this.$route.matched.map(record => {
          let path = record.path.length ? record.path : '/'
          // this is important to avoid unneeded changing on the original route path
          let route = {
            path,
            meta: record.meta,
            name: record.name
          }
          Object.keys(this.$route.params).forEach(param => {
            path = path.replace(':' + param, this.$route.params[param])
          }, this)

          route.path = path
          let resolvedRoute = this.$router.resolve({ path })
          return {
            text: this.getBreadcrumb(route),
            disabled: route.meta.breadcrumbDisabled || false,
            href: resolvedRoute.href
          }
        }, this)
        let result = breadcrumbs.filter(item => item.text === 'Dashboard' || !item.href.endsWith('/'))
        if (result.length > 0) {
          result[result.length - 1].disabled = true
        }
        return result
      }
    }
  }
}
</script>

<style scoped>

</style>
