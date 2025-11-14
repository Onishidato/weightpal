# Weightpal WordPress Plugin

AI-powered weight loss coaching using Google Gemini API.

## Installation

1. Upload the `weightpal` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Weightpal AI** in the admin menu
4. Enter your Google Gemini API key
5. Configure settings as needed

## Using the Gutenberg Block

### Adding the Block

1. Edit a page or post in WordPress
2. Click the **+** button to add a new block
3. Search for "**Weightpal**" or "**Weightpal AI Advisor**"
4. Click to add the block to your page

### Block Location

The block appears in the **Weightpal** category in the block inserter.

### Customizing the Block

After adding the block, use the **Block Settings** sidebar (right panel) to customize:

- **Content Settings:**
  - Toggle title visibility
  - Edit title text
  - Toggle description visibility
  - Edit description text
  - Customize button text

- **Color Settings:**
  - Background color
  - Text color
  - Accent color (button)

## Troubleshooting

### Block Not Appearing

If you cannot see the Weightpal block:

1. **Deactivate and reactivate the plugin:**
   - Go to Plugins → Deactivate Weightpal
   - Then Activate it again

2. **Clear WordPress cache:**
   - If using a caching plugin, clear all caches
   - Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)

3. **Check WordPress version:**
   - Requires WordPress 5.8 or higher
   - Go to Dashboard → Updates to check version

4. **Check for JavaScript errors:**
   - Open browser Developer Tools (F12)
   - Check the Console tab for errors
   - Look for any red error messages

5. **Verify file permissions:**
   ```bash
   cd wp-content/plugins/weightpal
   ls -la blocks/weightpal-advisor/
   ```
   All files should be readable (644 permissions)

6. **Check if Gutenberg is enabled:**
   - The block only works with the Gutenberg (Block) editor
   - Go to Settings → Writing and ensure Classic Editor is not forcing the old editor

### API Not Working

If the form submits but doesn't return results:

1. **Verify API key is configured:**
   - Go to Weightpal AI settings
   - Ensure your Gemini API key is entered

2. **Test the REST API endpoint:**
   ```bash
   curl -X POST https://yoursite.com/wp-json/weightpal/v1/advice \
     -H "Content-Type: application/json" \
     -d '{"weight":75,"height":175,"routine":"Office work","userQuery":"Help me lose weight"}'
   ```

3. **Check browser console for errors:**
   - Open Developer Tools (F12)
   - Submit the form
   - Check Console and Network tabs for errors

## REST API

### Endpoint

```
POST /wp-json/weightpal/v1/advice
```

### Request Body

```json
{
  "weight": 75,
  "height": 175,
  "routine": "I work in an office 9-5",
  "userQuery": "I want to lose 10kg in 3 months"
}
```

### Response

```json
{
  "success": true,
  "data": "AI-generated advice...",
  "bmi": 24.49
}
```

## Support

For issues or questions, please check:
- WordPress version compatibility (5.8+)
- PHP version (7.4+)
- Plugin conflicts (deactivate other plugins to test)

## Credits

Developed using WordPress Block API and Google Gemini AI.
