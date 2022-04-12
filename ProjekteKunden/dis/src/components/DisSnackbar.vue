<template>
    <v-snackbar
            v-model="snackbar.visible"
            :bottom="snackbar.y === 'bottom'"
            :left="snackbar.x === 'left'"
            :multi-line="snackbar.multiLine"
            :right="snackbar.x === 'right'"
            :timeout="snackbar.timeout"
            :top="snackbar.y === 'top'"
            :vertical="snackbar.mobile === true"
            :color="snackbar.color"
    >
        {{ snackbar.text }}
        <v-btn
                flat
                @click="closeSnackbar()"
        >
            Close
        </v-btn>
    </v-snackbar>
</template>

<script>
import { mapGetters, mapMutations } from 'vuex'
export default {
  name: 'DisSnackbar',
  computed: {
    ...mapGetters([
      'snackbar'
    ])
  },
  methods: {
    ...mapMutations({
      'closeSnackbar': 'CLOSE_SNACKBAR'
    })
  }
}
</script>

<style scoped>

</style>
