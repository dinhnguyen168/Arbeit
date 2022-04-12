<template>
    <input
           :value="value"
            v-bind="$attrs"
            v-on="listeners"
            type="hidden"
    />
</template>

<script>
export default {
  name: 'DisAutoIncrementInput',
  props: {
    'value': {
      type: Number
    }
  },
  computed: {
    listeners () {
      return {
        ...this.$listeners
        // add more listeners here if needed
      }
    }
  }
}
</script>

<style scoped>

</style>
