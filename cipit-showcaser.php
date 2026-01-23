<?php
/**
 * Plugin Name: CIPIT Showcaser
 * Plugin URI: https://github.com/Muchwat/cipit-showcaser.git
 * Description: A high-end ribbon slider with autoplay, 50/50 split layout, five tech decoration options, and adaptive contrast.
 * Version: 3.2
 * Author: Kevin Muchwat
 */

if (!defined('ABSPATH'))
    exit;

// 1. Register Custom Post Type & Taxonomy
add_action('init', function () {
    register_post_type('showcase', [
        'labels' => ['name' => 'Showcase', 'singular_name' => 'Item'],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('showcase_group', 'showcase', [
        'hierarchical' => true,
        'labels' => ['name' => 'Groups', 'singular_name' => 'Group'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
});

// 2. Add Metaboxes for Slide Settings
add_action('add_meta_boxes', function () {
    add_meta_box(
        'showcase_settings',
        'Slide Display Settings',
        'render_showcase_metabox',
        'showcase',
        'normal',
        'high'
    );
});

function render_showcase_metabox($post)
{
    wp_nonce_field('showcase_save_meta', 'showcase_meta_nonce');
    $tag = get_post_meta($post->ID, '_showcase_tag', true);
    $btn_text = get_post_meta($post->ID, '_showcase_btn_text', true);
    $btn_link = get_post_meta($post->ID, '_showcase_btn_link', true);
    $target = get_post_meta($post->ID, '_showcase_target', true);
    ?>
    <p>
        <label for="showcase_tag"><strong>Custom Tag</strong> (e.g. "Featured", "Limited")</label><br>
        <input type="text" id="showcase_tag" name="showcase_tag" value="<?php echo esc_attr($tag); ?>" style="width:100%;"
            placeholder="Overrides group name...">
    </p>
    <p>
        <label for="showcase_btn_text"><strong>Button Text</strong></label><br>
        <input type="text" id="showcase_btn_text" name="showcase_btn_text" value="<?php echo esc_attr($btn_text); ?>"
            style="width:100%;" placeholder="Explore Program">
    </p>
    <p>
        <label for="showcase_btn_link"><strong>Custom Link</strong> (Leave empty to use post link)</label><br>
        <input type="url" id="showcase_btn_link" name="showcase_btn_link" value="<?php echo esc_attr($btn_link); ?>"
            style="width:100%;" placeholder="https://...">
    </p>
    <p>
        <label for="showcase_target"><strong>Link Target</strong></label><br>
        <select id="showcase_target" name="showcase_target" style="width:100%;">
            <option value="_self" <?php selected($target, '_self'); ?>>Same Tab (_self)</option>
            <option value="_blank" <?php selected($target, '_blank'); ?>>New Tab (_blank)</option>
        </select>
    </p>
    <?php
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['showcase_meta_nonce']) || !wp_verify_nonce($_POST['showcase_meta_nonce'], 'showcase_save_meta'))
        return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    $fields = ['showcase_tag', 'showcase_btn_text', 'showcase_btn_link', 'showcase_target'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
});

// 3. Shortcode Implementation
add_shortcode('showcase', function ($atts) {
    $atts = shortcode_atts([
        'group' => '',
        'limit' => 6,
        'autoplay' => 'true',
        'time' => 5000,
        'decoration' => 'orbits', // pulses, orbits, brackets, signals, blueprint, or none
        'bg' => '#2a2c32'
    ], $atts);

    $interval = (intval($atts['time']) < 100) ? intval($atts['time']) * 1000 : intval($atts['time']);

    $primary_red = '#c02126';
    $is_primary_bg = (strtolower(trim($atts['bg'])) === $primary_red || strtolower(trim($atts['bg'])) === 'c02126') ? true : false;
    $contrast_color = $is_primary_bg ? '#ffffff' : $primary_red;
    $contrast_text = $is_primary_bg ? $primary_red : '#ffffff';

    $args = [
        'post_type' => 'showcase',
        'posts_per_page' => $atts['limit'],
    ];

    if (!empty($atts['group'])) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'showcase_group',
                'field' => 'slug',
                'terms' => $atts['group'],
            ]
        ];
    }

    $query = new WP_Query($args);
    if (!$query->have_posts())
        return '';

    $unique_id = uniqid();
    ob_start();
    ?>
    <div class="showcase-container" id="showcase-<?php echo $unique_id; ?>"
        data-autoplay="<?php echo esc_attr($atts['autoplay']); ?>" data-interval="<?php echo esc_attr($interval); ?>"
        style="--theme-bg: <?php echo esc_attr($atts['bg']); ?>; --theme-contrast: <?php echo $contrast_color; ?>; --theme-contrast-text: <?php echo $contrast_text; ?>;">

        <div class="showcase-slider-wrapper">
            <button class="nav-arrow prev" onclick="moveSlider('<?php echo $unique_id; ?>', -1)" aria-label="Previous">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </button>
            <button class="nav-arrow next" onclick="moveSlider('<?php echo $unique_id; ?>', 1)" aria-label="Next">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M9 18l6-6-6-6" />
                </svg>
            </button>

            <div class="showcase-slider-viewport" id="viewport-<?php echo $unique_id; ?>">
                <div class="showcase-slider">
                    <?php
                    $post_count = 0;
                    while ($query->have_posts()):
                        $query->the_post();
                        $post_id = get_the_ID();
                        $img = get_the_post_thumbnail_url($post_id, 'large');
                        $img = $img ? $img : 'https://via.placeholder.com/1600x500?text=No+Image';

                        $custom_tag = get_post_meta($post_id, '_showcase_tag', true);
                        $btn_text = get_post_meta($post_id, '_showcase_btn_text', true);
                        $btn_link = get_post_meta($post_id, '_showcase_btn_link', true);
                        $link_target = get_post_meta($post_id, '_showcase_target', true);

                        $display_tag = !empty($custom_tag) ? $custom_tag : $atts['group'];
                        $display_btn_text = !empty($btn_text) ? $btn_text : 'Explore Program';
                        $display_btn_link = !empty($btn_link) ? $btn_link : get_the_permalink();
                        $display_target = !empty($link_target) ? $link_target : '_self';
                        ?>
                        <div class="showcase-slide" data-index="<?php echo $post_count; ?>">
                            <div class="slide-content-wrap">
                                <div class="slide-media-area">
                                    <div class="slide-image" style="background-image: url('<?php echo esc_url($img); ?>');">
                                    </div>
                                    <div class="slide-cutout-overlay"></div>

                                    <div class="slide-decoration style-<?php echo esc_attr($atts['decoration']); ?>">
                                        <div class="vector-grid"></div>

                                        <?php if ($atts['decoration'] === 'pulses'): ?>
                                            <div class="pulse-point p1"></div>
                                            <div class="pulse-point p2"></div>
                                            <div class="pulse-point p3"></div>
                                        <?php elseif ($atts['decoration'] === 'orbits'): ?>
                                            <div class="orbit-system">
                                                <div class="orbit-ring r1"></div>
                                                <div class="orbit-ring r2 white"></div>
                                                <div class="orbit-ring r3"></div>
                                                <div class="orbit-ring r4 white"></div>
                                                <div class="orbit-center-dot"></div>
                                            </div>
                                        <?php elseif ($atts['decoration'] === 'brackets'): ?>
                                            <div class="bracket b-tl"></div>
                                            <div class="bracket b-br"></div>
                                            <div class="tech-triangle t1"></div>
                                            <div class="tech-triangle t2"></div>
                                            <div class="bracket-label">CIPIT</div>
                                        <?php elseif ($atts['decoration'] === 'signals'): ?>
                                            <div class="signal-wave s1"></div>
                                            <div class="signal-wave s2"></div>
                                            <div class="signal-wave s3"></div>
                                        <?php elseif ($atts['decoration'] === 'blueprint'): ?>
                                            <div class="blueprint-line h-line"></div>
                                            <div class="blueprint-line v-line"></div>
                                            <div class="coord c1">X: 104.22</div>
                                            <div class="coord c2">Y: 88.01</div>
                                            <div class="crosshair"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="slide-details">
                                    <div class="slide-text-group">
                                        <span class="slide-tag">
                                            <?php echo strtoupper(esc_html($display_tag)); ?>
                                        </span>
                                        <h3 class="slide-title">
                                            <?php the_title(); ?>
                                        </h3>
                                    </div>
                                    <div class="slide-action-group">
                                        <p class="slide-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 10); ?>
                                        </p>
                                        <a href="<?php echo esc_url($display_btn_link); ?>"
                                            target="<?php echo esc_attr($display_target); ?>" class="slide-btn">
                                            <?php echo esc_html($display_btn_text); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $post_count++;
                    endwhile;
                    wp_reset_postdata(); ?>
                </div>
            </div>

            <div class="showcase-indicators" id="indicators-<?php echo $unique_id; ?>">
                <?php for ($i = 0; $i < $post_count; $i++): ?>
                    <span class="dot <?php echo ($i === 0) ? 'active' : ''; ?>"
                        onclick="jumpToSlide('<?php echo $unique_id; ?>', <?php echo $i; ?>)"></span>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <style>
        .showcase-container {
            --theme-red: #c02126;
            --theme-bg: #2a2c32;
            --theme-contrast: #c02126;
            --theme-contrast-text: #ffffff;
            --theme-radius: 12px;
            --theme-btn-radius: 30px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 2.5rem 0;
            width: 100%;
            position: relative;
        }

        .showcase-slider-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: var(--theme-radius);
            background: var(--theme-bg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .showcase-slider-viewport {
            width: 100%;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            scroll-snap-type: x mandatory;
        }

        .showcase-slider-viewport::-webkit-scrollbar {
            display: none;
        }

        .showcase-slider {
            display: flex;
            padding: 0;
            margin: 0;
        }

        .showcase-slide {
            flex: 0 0 100%;
            scroll-snap-align: center;
        }

        .slide-content-wrap {
            position: relative;
            aspect-ratio: 16 / 5;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .slide-media-area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .slide-image {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .slide-cutout-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--theme-bg);
            mask-image: radial-gradient(circle 800px at -300px 50%, transparent 800px, black 800px);
            -webkit-mask-image: radial-gradient(circle 800px at -300px 50%, transparent 800px, black 800px);
            z-index: 2;
        }

        .slide-decoration {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            pointer-events: none;
        }

        .vector-grid {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background-image:
                radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 20px 20px, 100px 100px, 100px 100px;
            opacity: 0.8;
        }

        /* STYLE: PULSES */
        .pulse-point {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--theme-contrast);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--theme-contrast);
        }

        .pulse-point::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            transform: translate(-50%, -50%);
            border: 1px solid var(--theme-contrast);
            border-radius: 50%;
            animation: pulse-ring 3s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }

        .pulse-point.p1 {
            top: 20%;
            right: 15%;
        }

        .pulse-point.p2 {
            bottom: 30%;
            right: 40%;
            animation-delay: 1s;
        }

        .pulse-point.p3 {
            top: 50%;
            right: 10%;
            animation-delay: 2s;
        }

        /* STYLE: ORBITS */
        .orbit-system {
            position: absolute;
            top: 50%;
            right: 15%;
            width: 280px;
            height: 280px;
            transform: translateY(-50%);
        }

        .orbit-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }

        .orbit-ring.white {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .orbit-ring.r1 {
            width: 100%;
            height: 100%;
            animation: rotate-orbit 20s linear infinite;
            border-top-color: var(--theme-contrast);
        }

        .orbit-ring.r2 {
            width: 75%;
            height: 75%;
            animation: rotate-orbit 15s linear infinite reverse;
            border-right-color: rgba(255, 255, 255, 0.4);
        }

        .orbit-ring.r3 {
            width: 50%;
            height: 50%;
            animation: rotate-orbit 10s linear infinite;
            border-bottom-color: var(--theme-contrast);
        }

        .orbit-ring.r4 {
            width: 30%;
            height: 30%;
            animation: rotate-orbit 8s linear infinite reverse;
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .orbit-center-dot {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 6px;
            height: 6px;
            background: var(--theme-contrast);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 12px var(--theme-contrast);
        }

        /* STYLE: BRACKETS */
        .bracket {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 2px solid var(--theme-contrast);
            opacity: 0.3;
        }

        .bracket.b-tl {
            top: 15%;
            right: 45%;
            border-right: 0;
            border-bottom: 0;
        }

        .bracket.b-br {
            bottom: 15%;
            right: 10%;
            border-left: 0;
            border-top: 0;
        }

        .tech-triangle {
            position: absolute;
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 10px solid var(--theme-contrast);
            opacity: 0.4;
        }

        .tech-triangle.t1 {
            top: 25%;
            right: 12%;
            transform: rotate(45deg);
            animation: float-tri 4s ease-in-out infinite;
        }

        .tech-triangle.t2 {
            bottom: 25%;
            right: 42%;
            transform: rotate(-135deg);
            animation: float-tri 4s ease-in-out infinite reverse;
        }

        .bracket-label {
            position: absolute;
            bottom: 10%;
            right: 10%;
            color: var(--theme-contrast);
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: 700;
            opacity: 0.8;
            letter-spacing: 4px;
        }

        /* STYLE: SIGNALS */
        .signal-wave {
            position: absolute;
            left: 50%;
            width: 50%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--theme-contrast), transparent);
            opacity: 0.3;
            animation: flow-wave 4s linear infinite;
        }

        .signal-wave.s1 {
            top: 30%;
            animation-duration: 3s;
        }

        .signal-wave.s2 {
            top: 50%;
            animation-duration: 5s;
            animation-delay: -1s;
        }

        .signal-wave.s3 {
            top: 70%;
            animation-duration: 4s;
            animation-delay: -2s;
        }

        /* STYLE: BLUEPRINT */
        .blueprint-line {
            position: absolute;
            background: var(--theme-contrast);
            opacity: 0.15;
        }

        .blueprint-line.h-line {
            top: 50%;
            right: 5%;
            width: 40%;
            height: 1px;
        }

        .blueprint-line.v-line {
            top: 10%;
            right: 25%;
            width: 1px;
            height: 80%;
        }

        .coord {
            position: absolute;
            color: var(--theme-contrast);
            font-family: monospace;
            font-size: 9px;
            opacity: 0.5;
        }

        .coord.c1 {
            top: 45%;
            right: 26%;
        }

        .coord.c2 {
            top: 52%;
            right: 26%;
        }

        .crosshair {
            position: absolute;
            top: 50%;
            right: 25%;
            width: 20px;
            height: 20px;
            border: 1px solid var(--theme-contrast);
            transform: translate(50%, -50%);
            opacity: 0.4;
        }

        .crosshair::before,
        .crosshair::after {
            content: '';
            position: absolute;
            background: var(--theme-contrast);
        }

        .crosshair::before {
            top: 50%;
            left: -5px;
            width: 30px;
            height: 1px;
        }

        .crosshair::after {
            left: 50%;
            top: -5px;
            width: 1px;
            height: 30px;
        }

        @keyframes pulse-ring {
            0% {
                width: 6px;
                height: 6px;
                opacity: 1;
            }

            100% {
                width: 60px;
                height: 60px;
                opacity: 0;
            }
        }

        @keyframes rotate-orbit {
            from {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        @keyframes float-tri {

            0%,
            100% {
                transform: translateY(0) rotate(45deg);
                opacity: 0.4;
            }

            50% {
                transform: translateY(-10px) rotate(45deg);
                opacity: 0.6;
            }
        }

        @keyframes flow-wave {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                transform: translateX(-100%);
                opacity: 0;
            }
        }

        .slide-media-area::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, var(--theme-bg) 0%, rgba(42, 44, 50, 0) 50%);
            z-index: 1;
            pointer-events: none;
        }

        .slide-details {
            position: relative;
            z-index: 5;
            width: 100%;
            padding: 0 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            gap: 40px;
        }

        .slide-text-group {
            flex: 0 0 40%;
        }

        .slide-tag {
            background: var(--theme-contrast);
            color: var(--theme-contrast-text);
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .slide-title {
            font-size: clamp(1.4rem, 2.5vw, 2rem);
            margin: 0;
            font-weight: 700;
            line-height: 1.2;
        }

        .slide-action-group {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            max-width: 45%;
        }

        .slide-excerpt {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .slide-btn {
            background: var(--theme-contrast);
            color: var(--theme-contrast-text);
            padding: 12px 28px;
            border-radius: var(--theme-btn-radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .slide-btn:hover {
            background: #fff;
            color: var(--theme-red);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .showcase-indicators {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(4px);
            padding: 6px 10px;
            border-radius: 20px;
            z-index: 10;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
        }

        .dot.active {
            background: var(--theme-contrast);
            width: 20px;
            border-radius: 4px;
        }

        .nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 11;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.2);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .showcase-slider-wrapper:hover .nav-arrow {
            opacity: 1;
        }

        .nav-arrow:hover {
            background: var(--theme-contrast);
            border-color: var(--theme-contrast);
            transform: translateY(-50%) scale(1.15);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            color: var(--theme-contrast-text);
        }

        .nav-arrow.prev {
            left: 25px;
        }

        .nav-arrow.next {
            right: 25px;
        }

        @media (max-width: 992px) {
            .slide-content-wrap {
                aspect-ratio: 16 / 9;
            }

            .slide-details {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
                padding: 40px;
                gap: 20px;
            }

            .slide-action-group {
                text-align: left;
                align-items: flex-start;
                max-width: 100%;
            }

            .slide-excerpt {
                display: none;
            }

            .slide-cutout-overlay {
                display: none;
            }

            .nav-arrow {
                opacity: 0.5;
                width: 36px;
                height: 36px;
            }
        }
    </style>

    <script>
        const sliderInstances = {};
        function moveSlider(id, dir) {
            const vp = document.getElementById('viewport-' + id);
            const amt = vp.offsetWidth;
            if (vp.scrollLeft + (amt * dir) >= vp.scrollWidth) vp.scrollTo({ left: 0, behavior: 'smooth' });
            else if (vp.scrollLeft + (amt * dir) < 0) vp.scrollTo({ left: vp.scrollWidth, behavior: 'smooth' });
            else vp.scrollBy({ left: amt * dir, behavior: 'smooth' });
            resetAutoplay(id);
        }
        function jumpToSlide(id, idx) {
            const vp = document.getElementById('viewport-' + id);
            vp.scrollTo({ left: vp.offsetWidth * idx, behavior: 'smooth' });
            resetAutoplay(id);
        }
        function resetAutoplay(id) {
            if (sliderInstances[id]) {
                clearInterval(sliderInstances[id].timer);
                const container = document.getElementById('showcase-' + id);
                if (!container.matches(':hover')) { startAutoplay(id); }
            }
        }
        function startAutoplay(id) {
            const el = document.getElementById('showcase-' + id);
            const iv = parseInt(el.dataset.interval) || 5000;
            if (el.dataset.autoplay === 'true') { sliderInstances[id] = { timer: setInterval(() => moveSlider(id, 1), iv) }; }
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.showcase-slider-viewport').forEach(vp => {
                const id = vp.id.replace('viewport-', '');
                const dots = document.querySelectorAll('#indicators-' + id + ' .dot');
                const container = document.getElementById('showcase-' + id);
                vp.addEventListener('scroll', () => {
                    const idx = Math.round(vp.scrollLeft / vp.offsetWidth);
                    dots.forEach((d, i) => d.classList.toggle('active', i === idx));
                }, { passive: true });
                startAutoplay(id);
                container.addEventListener('mouseenter', () => { if (sliderInstances[id]) clearInterval(sliderInstances[id].timer); });
                container.addEventListener('mouseleave', () => { startAutoplay(id); });
            });
        });
    </script>
    <?php
    return ob_get_clean();
});