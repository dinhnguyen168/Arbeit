export default {
  computed: {
    userIsAdmin () {
      return this.$store.state.loggedInUser && this.$store.state.loggedInUser.roles.includes('sa')
    },
    userIsDeveloper () {
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes('developer') || this.$store.state.loggedInUser.roles.includes('sa'))
    },
    userIsOperator () {
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes('operator') || this.$store.state.loggedInUser.roles.includes('developer') || this.$store.state.loggedInUser.roles.includes('sa'))
    },
    userIsViewer () {
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes('viewer') || this.$store.state.loggedInUser.roles.includes('operator') || this.$store.state.loggedInUser.roles.includes('developer') || this.$store.state.loggedInUser.roles.includes('sa'))
    }
  }
}
