# Design System Strategy: The Academic Monolith

## 1. Overview & Creative North Star
**Creative North Star: The Architectural Editorial**
This design system moves away from the "disposable web" look of typical SSO portals and toward an aesthetic of permanence, security, and prestige. We are treating the educational portal not as a utility, but as a digital campus—a space that feels as structured and authoritative as a limestone library. 

To achieve this, we break the "template" mold by utilizing **intentional asymmetry** and **exaggerated white space**. We avoid the cluttered "dashboard" look in favor of high-end editorial layouts. By using a sophisticated contrast between a massive, human-centric display scale (`manrope`) and a technical, ultra-legible body scale (`inter`), we signal that this platform is both high-tech and deeply human.

---

## 2. Colors & Surface Philosophy
The palette is rooted in a deep, authoritative navy (`primary: #000666`) balanced by an airy, expansive neutral system.

### The "No-Line" Rule
Standard UI relies on 1px borders to separate content. This design system **prohibits 1px solid borders** for sectioning. Boundaries must be defined solely through:
- **Tonal Shifts:** Placing a `surface-container-low` component on a `surface` background.
- **Negative Space:** Using the Spacing Scale (specifically `8` to `12`) to create mental boundaries.

### Surface Hierarchy & Nesting
Treat the UI as a series of physical layers. We use "Tonal Nesting" to create depth:
1.  **Base:** `surface` (#f8f9fa) – The infinite canvas.
2.  **Sectioning:** `surface-container-low` (#f3f4f5) – To define large content areas.
3.  **Interaction Hubs:** `surface-container-lowest` (#ffffff) – Used for primary cards and form containers to make them "pop" forward naturally.

### The "Glass & Signature Texture" Rule
To prevent the portal from feeling "flat" or "cheap," primary action areas or hero headers should utilize a **Signature Gradient**. Transition from `primary` (#000666) to `primary_container` (#1a237e) at a 135-degree angle. For floating navigation or modal overlays, apply `surface_container_lowest` at 80% opacity with a `20px` backdrop-blur to create a "Frosted Scholastic" effect.

---

## 3. Typography
We employ a dual-type system to balance academic tradition with modern accessibility.

*   **Display & Headlines (Manrope):** Chosen for its geometric precision and modern "tech-intellectual" feel. Use `display-lg` for login greetings to create a bold, editorial welcome.
*   **Body & Labels (Inter):** The workhorse. We use `inter` for all functional data. Its high x-height ensures that even at `body-sm` (0.75rem), security instructions and form labels remain perfectly legible.
*   **Tonal Authority:** Use `on_surface_variant` (#454652) for secondary labels. It provides enough contrast for accessibility while softening the UI, preventing the "stark black on white" look that causes eye strain during long study sessions.

---

## 4. Elevation & Depth
Depth is a functional tool for security; it shows the user exactly where their focus should be.

*   **Layering Principle:** Instead of shadows, stack `surface-container-highest` headers on top of `surface-container-low` bodies. This "flat-depth" mimics high-end architectural drawings.
*   **Ambient Shadows:** When a card must float (e.g., a SSO selection modal), use an ultra-diffused shadow: `box-shadow: 0 20px 40px rgba(0, 7, 103, 0.06);`. Note the use of the `on_primary_fixed` tint in the shadow—never use pure grey.
*   **The "Ghost Border" Fallback:** If a boundary is required for accessibility in high-glare environments, use `outline_variant` (#c6c5d4) at **15% opacity**. It should be felt, not seen.

---

## 5. Components

### Buttons
*   **Primary:** Background: `primary` (#000666), Text: `on_primary` (#ffffff). Shape: `md` (0.375rem).
*   **Secondary/Google Auth:** Use `surface_container_lowest` with a "Ghost Border." Google branding must follow official hex/logo guidelines but should be housed in our `md` radius container to maintain system harmony.
*   **Tertiary:** No background. Use `primary` text with an underline that only appears on hover.

### Input Fields
*   **Structure:** No bottom line. Use a solid `surface_container_high` background with a `sm` radius. 
*   **Focus State:** Transition the background to `surface_container_lowest` and add a 2px `primary` bottom-border. This "lifting" effect signals the field is active.

### Cards & Lists
*   **Forbid Dividers:** Do not use lines between list items. Use `spacing-4` vertical padding and a `surface-variant` hover state to separate items.
*   **Educational Context:** Cards should use `xl` (0.75rem) roundedness for a friendlier, "approachable" academic feel.

### Specialized SSO Components
*   **Identity Chip:** A small `tertiary_fixed` (#a3f69c) pill at the top of the screen showing "Logged in as [User]" to provide immediate security reassurance using the "friendly green" accent.

---

## 6. Do’s and Don’ts

### Do:
*   **Use Asymmetric Margins:** In the login flow, offset the central card slightly to the right or left of the viewport to create a modern, editorial composition.
*   **Embrace "Large" Type:** If a screen only has one question (e.g., "Enter your MFA code"), use `headline-lg` instead of standard body text.
*   **Prioritize Breathing Room:** Use `spacing-16` or `spacing-24` between major sections to emphasize security and calm.

### Don’t:
*   **Don't use "Alert Red" for everything:** Use `error_container` for soft warnings; reserve `error` (#ba1a1a) only for critical blocking issues.
*   **Don't use pure black:** Use `on_surface` (#191c1d) for text. It keeps the "Navy/Professional" soul of the system intact.
*   **Don't use standard shadows:** If it looks like a default CSS shadow, it’s too heavy. Soften and tint it.