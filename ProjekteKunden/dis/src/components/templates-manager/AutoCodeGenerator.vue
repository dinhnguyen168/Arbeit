<template>
    <v-dialog :value="value" max-width="920" persistent>
        <v-card>
            <v-card-title>
                <h1>Model Code Generator</h1>
            </v-card-title>
            <v-card-text>
                <code-generator v-bind="$attrs" ref="codeGenerator"/>
            </v-card-text>
            <v-card-actions>
                <v-spacer/>
                <v-btn @click="$emit('input', false)" flat color="red">close</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import CodeGenerator from './CodeGenerator'
export default {
  name: 'AutoCodeGenerator',
  components: { CodeGenerator },
  props: {
    value: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      ready: true
    }
  },
  methods: {
    async generate () {
      try {
        await this.$refs.codeGenerator.cgView()
        const shouldOverwrite = this.$refs.codeGenerator.files.findIndex(item => item.operation === 'overwrite') > -1
        if (!shouldOverwrite) {
          for (const k in this.$refs.codeGenerator.answers) {
            this.$refs.codeGenerator.answers[k] = true
            await this.$nextTick()
          }
          await this.$refs.codeGenerator.cgView(true)
          return 'generated'
        } else {
          return 'waitingForUser'
        }
      } catch (e) {
        console.log('auto generator', e)
      }
    }
  }
}
</script>

<style scoped>

</style>
