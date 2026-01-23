# CIPIT Showcaser 🎨

A premium, high-end WordPress ribbon slider plugin designed for elegant program and event showcases. Developed by Kevin Muchwat, this plugin focuses on architectural geometry, smooth interactions, and strict adherence to modern UI design principles.

---

## 🚀 Installation

1. Download the `showcase-plugin.php` file.
2. Upload it to your WordPress directory:  
   `/wp-content/plugins/cipit-showcaser/`
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Look for the **Showcase** menu item in your WordPress admin sidebar to start adding slides.

---

## 🛠 Usage

### Shortcode

Use the following shortcode in your pages, posts, or widgets:

```bash
[showcase group="ai" limit="5" time="5000" decoration="blueprint" bg="#c02126" excerpt="15"]
```

### Parameters

| Parameter  | Default | Description |
|------------|---------|-------------|
| `group`    | `''`    | The slug of the Showcase Group (Taxonomy) to display. |
| `limit`    | `6`     | Maximum number of slides to show. |
| `time`     | `5000`  | The autoplay interval in milliseconds (e.g., `3000` for 3 seconds). |
| `autoplay` | `true`  | Set to `false` to disable automatic sliding. |
| `decoration` | `pulses` | Options: `pulses`, `orbits`, `brackets`, `signals`, `blueprint`, `none`. |
| `bg`       | `#c02126`  | Custom background color (hex). UI contrast adapts automatically. |
| `excerpt` | `10` | The number of words to display in the slide description/excerpt. |



