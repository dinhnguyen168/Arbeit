<template>
    <v-layout justify-center align-center class="c-404">
        <v-flex shrink>
            <span class="c-404__code">
                HTTP Error 404
            </span>
        </v-flex>
        <v-flex shrink>
            <p class="title">
                Page not found.
            </p>
            <router-link to="/">Back to the Dashboard</router-link>
        </v-flex>
    </v-layout>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>

<style>
.c-404__code {
    font-size: 10rem;
    margin-right: 10px;
}
</style>
