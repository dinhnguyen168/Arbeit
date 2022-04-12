<template>
  <div class="c-dis-qr-code-scanner">
      <v-text-field :loading="isLoading" :disabled="isLoading" v-model="qrCode" single-line hide-details autofocus clearable type="text" ref="dialogInput" @keyup.native.enter="onEnter" @click:clear="clearFoundForms" label="Enter (or scan) igsn code" ></v-text-field>
      <v-list v-if="foundForms.length">
        <v-list-tile v-for="(form, index) of foundForms" :key="index" :to="`/forms/${form.name}-form/${form.id}?${filterToString(form.filter)}`">
          {{form.name}}@{{form.id}}, {{ form.info }}
        </v-list-tile>
      </v-list>
  </div>
</template>
<script>
import AppService from '../services/AppService'
import urlFilter from '../mixins/urlFilter'

export default {
  name: 'DisQrCodeScanner',
  mixins: [urlFilter],
  props: {
    isGlobalScanner: {
      type: Boolean,
      default: true
    }
  },
  data () {
    return {
      isActive: false,
      qrCode: '',
      isLoading: false,
      foundForms: []
    }
  },
  computed: {
    dialog: {
      get () {
        return this.isGlobalScanner && this.isActive
      },
      set (value) {
        if (this.isGlobalScanner) {
          this.isActive = value
        }
      }
    }
  },
  created () {
    this.appService = new AppService()
  },
  mounted () {},
  methods: {
    toggleActive () {
      this.isActive = !this.isActive
    },
    clearFoundForms () {
      this.foundForms = []
    },
    async onEnter () {
      this.isLoading = true
      try {
        const foundForms = []
        const data = await this.appService.findIgsn(this.qrCode)
        for (const form of this.$store.state.templates.summary.forms) {
          for (const item of data) {
            if (item.data_model === form.dataModel) {
              foundForms.push({
                id: item.id,
                name: form.name,
                info: item.info,
                filter: item.filter
              })
            }
          }
        }
        this.foundForms = foundForms
        if (foundForms.length === 1) {
          this.$router.push(`/forms/${foundForms[0].name}-form/${foundForms[0].id}?${this.filterToString(foundForms[0].filter)}`)
          this.isActive = false
        }
      } catch (error) {
        console.log(error)
        this.$dialog.notify.warning(error.response.data.message, { timeout: 30000 })
      } finally {
        this.isLoading = false
      }
    }
  },
  watch: {
    async dialog (value) {
      if (value) {
        await this.$nextTick()
        this.$refs.dialogInput.focus()
      } else {
        this.foundForms = []
        this.qrCode = ''
      }
    }
  }
}
</script>
<style></style>
