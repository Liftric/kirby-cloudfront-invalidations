<template>
  <k-view
    className="k-test-view">
    <k-header>CloudFront Invalidations</k-header>
    <k-grid
      gutter="small">
      <k-column
        width="1/1">
        <k-text
          theme="help">Every content is cached and sometimes you need to free the cache up.
        </k-text>
      </k-column>
      <k-column
        width="1/1">
        <k-button
          icon="refresh"
          @click="invalidate">Invalidate
        </k-button>
      </k-column>
    </k-grid>
  </k-view>
</template>

<script>
export default {
  methods: {
    invalidate() {
      this.$api.get('cloudfront/invalidate').then(() => {
        console.log('invalidation was sent');
      })
    }
  }
};
</script>
