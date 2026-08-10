</div><!-- #content.site-content -->

<?php wp_footer(); ?>

<style>
    .hover-underline:hover {
        text-decoration: underline !important;
        text-underline-offset: 4px;
    }

    .social-btn:hover {
        background-color: #f8f9fa !important;
        color: #111 !important;
        border-color: #ddd !important;
    }

    /* ============================================================
           CUSTOM FOOTER STYLES
           ============================================================ */
    .custom-footer {
        background-color: #ffffff;
        color: #333333;
        padding: 40px 0;
        font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        border-top: 1px solid #f0f0f0;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100% !important;
        box-sizing: border-box;
    }

    @media (min-width: 769px) {
        .custom-footer {
            margin-left: 64px;
            width: calc(100% - 64px) !important;
        }
    }

    .footer-inner-container {
        width: 100%;
        max-width: 1380px !important;
        margin-left: auto;
        margin-right: auto;
        padding-left: 48px;
        padding-right: 48px;
        box-sizing: border-box;
    }

    @media (min-width: 1200px) {
        .footer-inner-container {
            padding-left: 56px;
            padding-right: 56px;
        }
    }

    @media (max-width: 768px) {
        .custom-footer {
            margin-left: 0;
            width: 100% !important;
            padding: 30px 0;
        }

        .footer-inner-container {
            padding-left: 16px;
            padding-right: 16px;
        }
    }

    .footer-col h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: #111;
    }

    /* Column 1 */
    .footer-col-1 h3 {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 15px;
    }

    .footer-col-1 p {
        color: #666;
        margin-bottom: 24px;
        font-size: 0.95rem;
        line-height: 1.5;
        max-width: 250px;
    }

    .newsletter-form {
        display: flex;
        align-items: center;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 6px;
        background: #fff;
        max-width: 280px;
    }

    .newsletter-form input {
        border: none;
        outline: none;
        padding: 8px 12px;
        flex-grow: 1;
        font-size: 0.95rem;
        background: transparent;
    }

    .newsletter-form button {
        background-color: #111;
        color: #fff;
        border: none;
        border-radius: 6px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .newsletter-form button:hover {
        background-color: #333;
    }

    /* Column 2 & 3 */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 14px;
    }

    .footer-links a {
        text-decoration: none;
        color: #555;
        transition: color 0.2s;
        font-size: 0.95rem;
    }

    .footer-links a:hover {
        color: #111;
    }

    .footer-contact p {
        color: #555;
        margin-bottom: 14px;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    /* Column 4 */
    .social-icons {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
    }

    .social-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 1px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #555;
        text-decoration: none;
        transition: all 0.2s;
    }

    .social-icon:hover {
        background-color: #f5f5f5;
        color: #111;
    }

    /* Theme Toggle */
    .theme-toggle-container {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #555;
    }

    .theme-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
    }

    .theme-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #111;
        transition: .4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }



    /* Aurora Background Effect */
    :root {
        --aurora-white: #ffffff;
        --aurora-black: #000000;
        --aurora-transparent: transparent;
        --aurora-blue-500: #3b82f6;
        --aurora-indigo-300: #a5b4fc;
        --aurora-blue-300: #93c5fd;
        --aurora-violet-200: #ddd6fe;
        --aurora-blue-400: #60a5fa;
    }

    body,
    .site,
    #page,
    #content,
    .site-content {
        background-color: transparent !important;
    }

    .aurora-background {
        position: fixed;
        inset: 0;
        overflow: hidden;
        z-index: -1;
        pointer-events: none;
        background-color: #f8fafc;
        /* base color */
    }

    .aurora-effect {
        position: absolute;
        inset: -10px;
        opacity: 0.35;
        will-change: transform;
        --white-gradient: repeating-linear-gradient(100deg, var(--aurora-white) 0%, var(--aurora-white) 7%, var(--aurora-transparent) 10%, var(--aurora-transparent) 12%, var(--aurora-white) 16%);
        --dark-gradient: repeating-linear-gradient(100deg, var(--aurora-black) 0%, var(--aurora-black) 7%, var(--aurora-transparent) 10%, var(--aurora-transparent) 12%, var(--aurora-black) 16%);
        --aurora: repeating-linear-gradient(100deg, var(--aurora-blue-500) 10%, var(--aurora-indigo-300) 15%, var(--aurora-blue-300) 20%, var(--aurora-violet-200) 25%, var(--aurora-blue-400) 30%);

        background-image: var(--white-gradient), var(--aurora);
        background-size: 300% 200%;
        background-position: 50% 50%, 50% 50%;
        filter: blur(20px) invert(100%);
        mask-image: radial-gradient(ellipse at 100% 0%, black 10%, var(--aurora-transparent) 70%);
        -webkit-mask-image: radial-gradient(ellipse at 100% 0%, black 10%, var(--aurora-transparent) 70%);
    }

    .aurora-effect::after {
        content: "";
        position: absolute;
        inset: 0;
        background-image: var(--white-gradient), var(--aurora);
        background-size: 200% 100%;
        animation: animate-aurora 60s linear infinite;
        background-attachment: fixed;
        mix-blend-mode: difference;
    }

    @keyframes animate-aurora {
        from {
            background-position: 50% 50%, 50% 50%;
        }

        to {
            background-position: 350% 50%, 350% 50%;
        }
    }

    body.dark-mode .aurora-background {
        background-color: #18181b;
    }

    body.dark-mode .aurora-effect {
        background-image: var(--dark-gradient), var(--aurora);
        filter: blur(10px) invert(0%);
    }

    body.dark-mode .aurora-effect::after {
        background-image: var(--dark-gradient), var(--aurora);
    }
</style>

<div class="aurora-background">
    <div class="aurora-effect"></div>
</div>

</body>

</html>