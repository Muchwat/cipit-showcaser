# CIPIT Showcaser 🎨

A premium, high-end WordPress ribbon slider plugin designed for elegant program and event showcases. Developed by Kevin Muchwat, this plugin focuses on architectural geometry, smooth interactions, and strict adherence to modern UI design principles.

---

## ✨ Features

- **Architectural Design**: Features a distinctive *"sharp lens"* circular cutout effect with a perfect 50/50 split between content and imagery.
- **Intelligent Autoplay**: Smooth transitions every 5 seconds (configurable) with strict hover-pause logic—the slider never moves while the user is interacting with it.
- **Custom Metaboxes**: Full control over every slide:
  - **Custom Tag**: Override category slugs with "Featured", "New", or custom labels.
  - **Dynamic Buttons**: Customize the "Call to Action" text per slide.
  - **Custom Links**: Point buttons to external registration forms or internal pages.
  - **Link Targets**: Choose between opening links in the same tab or a new tab (`_blank`).
- **Shortcode Powered**: Easily deploy anywhere on your site with flexible parameters.
- **Responsive & Lightweight**: No heavy libraries. Uses native CSS masking and vanilla JavaScript for high performance.

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
[showcase group="artificial-intelligence" limit="5" time="3000"]
```

### Parameters

| Parameter  | Default | Description |
|------------|---------|-------------|
| `group`    | `''`    | The slug of the Showcase Group (Taxonomy) to display. |
| `limit`    | `6`     | Maximum number of slides to show. |
| `time`     | `5000`  | The autoplay interval in milliseconds (e.g., `3000` for 3 seconds). |
| `autoplay` | `true`  | Set to `false` to disable automatic sliding. |

---

### 🎨 Design Specs

The plugin is hard-coded to respect a premium aesthetic:

- **Primary Color**: `#c02126` *(CIPIT Red)*  
- **Secondary Color**: `#2a2c32` *(Deep Charcoal)*  
- **Border Radius**:  
  - `12px` for containers  
  - `30px` for buttons  
- **Layout**: `16:5` ribbon aspect ratio for a non-intrusive "showcase" feel