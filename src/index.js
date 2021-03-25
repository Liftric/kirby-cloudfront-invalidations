import View from "./components/View.vue";

panel.plugin("liftric/cloudfrontinvalidations", {
  views: {
    example: {
      component: View,
      icon: "preview",
      label: "Example"
    }
  }
});
