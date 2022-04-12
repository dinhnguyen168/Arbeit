<template>
    <div class="c-simple-panel">
        <div class="c-simple-panel__head">
            <slot name="head"></slot>
            <v-spacer></v-spacer>
            <v-btn small icon @click="fullView = !fullView">
                <v-icon v-if="!this.fullView">arrow_drop_down</v-icon>
                <v-icon v-if="this.fullView">arrow_drop_up</v-icon>
            </v-btn>
        </div>
        <div class="c-simple-panel__content" v-if="fullView">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
  name: 'DisSimplePanel',
  props: {
    newColumnAdded: {
      type: Boolean
    }
  },
  created () {
    if (this.newColumnAdded) this.fullView = this.newColumnAdded
  },
  data () {
    return {
      fullView: false
    }
  }
}
</script>

<style scoped lang="stylus">
    .c-simple-panel
        &__head
            display flex
            align-items center
            padding 2px 5px
        &__content
            display flex
</style>
