<template>
    <v-container fluid grid-list-lg>
        <v-layout justify-center align-center wrap>
            <v-flex shrink v-for="appForm in forms" :key="appForm.key">
                <v-hover>
                    <router-link
                            class="form-link"
                            tag="v-card"
                            slot-scope="{hover}"
                            :class="{ 'elevation-12': hover, 'elevation-2': !hover }"
                            :to="`/forms/${appForm.key}-form`">
                        <v-card-title class="title">
                            {{appForm.label}}
                        </v-card-title>
                        <div :class="{ 'bottom-indicator': true, 'green':  specializedForms.includes(`${appForm.key}-form`), 'red':  !specializedForms.includes(`${appForm.key}-form`)}">
                            &nbsp;
                        </div>
                    </router-link>
                </v-hover>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
import generatedRouts from '../router/generatedRoutes'
export default {
  name: 'DisAppForms',
  data () {
    let specializedForms = []
    generatedRouts.map(item => {
      specializedForms.push(item.path.slice(0, item.path.indexOf('/')))
    })
    return {
      specializedForms: specializedForms
    }
  },
  computed: {
    forms () {
      let forms = []
      for (let module in this.$store.state.appForms) {
        if (Array.isArray(this.$store.state.appForms[module])) {
          forms = [
            ...forms,
            ...this.$store.state.appForms[module]
          ]
        }
      }
      return forms
    }
  }
}
</script>

<style scoped>
    .form-link {
        cursor: pointer;
    }
    .bottom-indicator {
        line-height: 2px;
    }
</style>
