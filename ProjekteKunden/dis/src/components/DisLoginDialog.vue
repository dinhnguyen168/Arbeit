<template>
    <div class="message-box dialog-mask">
        <v-card ref="form" class="dialog-content">
            <v-card-text>
                <h1>Login</h1>
                <v-text-field v-model="username" label="Username / Email"></v-text-field>
                <v-text-field v-model="password" label="Password" type="password"></v-text-field>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="primary" flat @click="$close({username, password})">Ok</v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script>
export default {
  name: 'DisLoginDialog',
  data () {
    return {
      username: '',
      password: ''
    }
  }
}
</script>

<style>
    .dialog-mask {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-align: center;
        align-items: center;
        -ms-flex-pack: center;
        justify-content: center;
        background-color: rgba(0,0,0,.33);
    }
    /*.message-box .dialog-content {*/
        /*min-width: 240px;*/
        /*text-align: center;*/
        /*font-size: 16px;*/
    /*}*/
    /*.dialog-content {*/
        /*margin: 0 20px;*/
        /*padding: 20px 30px;*/
        /*border: 1px solid #ebeef5;*/
        /*border-radius: 4px;*/
        /*background-color: #fff;*/
        /*box-shadow: 0 2px 12px 0 rgba(0,0,0,.1);*/
        /*color: #303133;*/
    /*}*/
</style>
