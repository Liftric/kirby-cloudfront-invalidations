import View from "./components/View.vue";

panel.plugin("liftric/cloudfrontinvalidations", {
  views: {
    cloudfront: {
      component: View,
      icon: "refresh",
      label: "CloudFront"
    }
  }
});
