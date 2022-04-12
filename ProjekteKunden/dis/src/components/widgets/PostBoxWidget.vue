<template>
    <base-widget ref="baseWidget" :widget="widget" :editMode="editMode">
        <v-layout column class="posts">
            <v-flex>
                <v-layout class="posts_form" row align-center>
                    <v-flex grow>
                        <v-textarea rows="2" auto-grow label="Post Text" hide-details outline v-model="newPostText"></v-textarea>
                    </v-flex>
                    <v-flex shrink>
                        <v-btn icon @click="submitPost" :loading="isPosting">
                            <v-icon>send</v-icon>
                        </v-btn>
                    </v-flex>
                </v-layout>
            </v-flex>
            <v-progress-linear indeterminate v-if="isLoading"/>
            <v-flex class="scroll-y" style="height: 300px">
                <v-timeline light dense>
                    <v-timeline-item v-for="post in posts" :key="post.id" right small >
                        <v-card class="elevation-2">
                            <v-card-text>
                                <div class="d-flex body-2">{{post.author}} <v-spacer></v-spacer> {{post.created_at * 1000  | formatTimestamp}}</div>
                                {{post.text}}
                                <div class="text-xs-right">
                                    <v-btn small icon class="ma-0" :disabled="isLoading" @click="() => deletePost(post.id)"><v-icon color="red">delete</v-icon></v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-timeline-item>
                </v-timeline>
                <v-btn v-if="postsMeta.currentPage < postsMeta.pageCount" @click="() => getPosts(postsMeta.currentPage + 1, true)" :loading="isLoading">Load Older</v-btn>
            </v-flex>
        </v-layout>
    </base-widget>
</template>

<script>
import BaseWidget from './BaseWidget'
import PostBoxService from '../../services/PostBoxService'
const postBoxService = new PostBoxService()
export default {
  name: 'PostBoxWidget',
  components: { BaseWidget },
  props: {
    widget: {
      type: Object,
      required: true
    },
    editMode: {
      type: Boolean,
      required: true
    }
  },
  data () {
    return {
      posts: [],
      postsMeta: {
        currentPage: 1,
        pageCount: 2,
        perPage: 5,
        totalCount: 7
      },
      newPostText: '',
      isPosting: false,
      isLoading: false
    }
  },
  computed: {
    userCanDeletePost () {
      return this.$store.state.loggedInUser && (this.$store.state.loggedInUser.roles.includes(`sa`) || this.$store.state.loggedInUser.roles.includes(`developer`))
    }
  },
  mounted () {
    this.getPosts()
  },
  methods: {
    async getPosts (page = 1, append = false) {
      try {
        this.isLoading = true
        const data = await postBoxService.get({ 'per-page': 5, 'sort': '-created_at', page })
        if (append) {
          this.posts.push(...data.items)
        } else {
          this.posts = data.items
        }
        this.postsMeta = data._meta
      } catch (error) {
        console.log(error)
      } finally {
        this.isLoading = false
      }
    },
    async submitPost () {
      try {
        this.isPosting = true
        const data = await postBoxService.post({ text: this.newPostText })
        this.posts.unshift(data)
        this.newPostText = ''
        await this.getPosts()
      } catch (error) {
        console.log(error)
        this.$dialog.notify.warning(error.message)
      } finally {
        this.isPosting = false
      }
    },
    async deletePost (postId) {
      try {
        this.isLoading = true
        const confirm = await this.$dialog.confirm({
          title: 'Delete a Post',
          text: 'This action cannot be reverted. Are you sure?'
        })
        if (confirm) {
          await postBoxService.delete(postId)
          await this.getPosts()
        }
      } catch (error) {
        console.log(error)
        this.$dialog.notify.warning(error.message)
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>
