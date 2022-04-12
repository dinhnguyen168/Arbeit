export default {
  methods: {
    stringToFilter (str) {
      const regex = /🔎([^〰]+)〰([^$|🔎]+)/g
      const matches = {}
      let match = regex.exec(str)
      while (match !== null) {
        matches[match[1]] = parseInt(match[2])
        match = regex.exec(str)
      }
      return matches
    },
    filterToString (filter) {
      const filterComponents = Object.keys(filter)
      let filterString = ``
      filterComponents.forEach(key => {
        if (filter[key]) {
          filterString += `🔎${key}〰${filter[key]}`
        }
      })
      return filterString
    }
  },
  computed: {
    urlFilter: {
      get () {
        try {
          if (this.$route.query.filter) {
            // const regex = /🔎([^〰]+)〰([^$|🔎]+)/g
            // const matches = {}
            // let match = regex.exec(this.$route.query.filter)
            // while (match !== null) {
            //   matches[match[1]] = parseInt(match[2])
            //   match = regex.exec(this.$route.query.filter)
            // }
            return this.stringToFilter(this.$route.query.filter)
          }
        } catch (e) {
          console.log('an error happened while evaluating urlFilter')
        }
        return null
      },
      set (value) {
        // const filterComponents = Object.keys(value)
        // let filterString = ``
        // filterComponents.forEach(key => {
        //   if (value[key]) {
        //     filterString += `🔎${key}〰${value[key]}`
        //   }
        // })
        let route = Object.assign({}, { name: this.$route.name, params: this.$route.params }, { query: {} })
        let filter = this.filterToString(value)
        if (filter.length) route.query = { filter: filter }

        this.$router.replace(route).catch(err => {
          if (err.name !== 'NavigationDuplicated' && !err.message.includes('Avoided redundant navigation to current location')) {
            console.error(err)
            throw err
          }
        })
      }
    }
  }
}
