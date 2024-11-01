(function ($) {
    var el                = wp.element.createElement,
        registerBlockType = wp.blocks.registerBlockType,
        SVG               = wp.components.SVG,
        Path              = wp.components.Path,
        withInstanceId    = wp.compose.withInstanceId;

    var blockStyle = {
        border:     'solid 1px lightgray',
        padding:    '20px',
        boxShadow:  '5px 5px 5px lightgray',
        fontFamily: 'sans-serif'
    };
    var campaigns  = null;
    var options    = null;

    registerBlockType('vessel/gutenberg-campaign-block', {
        title: 'Vessel Campaign Map',

        description: "For Travel, Food, and Lifestyle bloggers, Vessel increases engagement rate and creates an interactive map-based user experience. Optimized for vertical video!",

        category: 'embed',

        icon: el(
            SVG,
            {
                viewBox: "0 0 100 108",
                xmlns:   "http://www.w3.org/2000/svg"
            },
            el(
                Path,
                {
                    d: "M73.55,36.12c-6.66-1.7-13.66-1.65-20.48-1.83a96.29,96.29,0,0,0-17,1.26c-4.56.83-8.74,1.85-10.9,6.23-3.44,7-3.07,16.36-2.5,24,.29,3.77.95,7.39,3.36,10.45,1.9,2.41,5,3,7.89,3.62a101.21,101.21,0,0,0,19.67,1.75,104.1,104.1,0,0,0,18.74-1.62c2.64-.47,5.88-1,7.92-2.91,2.8-2.61,3.66-6.8,4-10.41.71-7.65,1-16.82-2-24C80.43,38.58,77.69,37.18,73.55,36.12Zm8.81,30.5c-.34,3.48-1.33,8-4.64,9.9-2.55,1.44-6.11,1.74-8.94,2.12a116.4,116.4,0,0,1-14.35,1.08h-2a112.37,112.37,0,0,1-15-1.18c-2.74-.39-6.29-.7-8.67-2.24-3.28-2.12-4-7-4.3-10.56C24,59,23.77,51.3,25.91,44.79c1.87-5.71,6.4-6.84,12-7.69a103.06,103.06,0,0,1,15.21-1c6.22.16,12.55.13,18.66,1.45,5.22,1.14,8,2.82,9.5,8C83.08,52.22,83,59.81,82.36,66.62Z"
                }
            ),
            el(
                Path,
                {
                    d: "M105,40.19a4,4,0,1,0-5.52,3.69L98.1,51.35a10.9,10.9,0,0,1-1.3-1.77c-1.38-2.46-1.63-5.31-1.84-8.07-.33-4.35-.58-8.72-1.93-12.91C88,13,69.55,4.91,54.3,4.66a.47.47,0,0,0-.17,0,.55.55,0,0,0-.18,0c-15.75.27-34.6,8.86-39.1,25.19-1.13,4.1-1.19,8.3-1.66,12.5a28.52,28.52,0,0,1-1.35,6.72c-.91,2.42-2.77,3.23-4.25,5.16C5.1,57.51,5.38,63,5.69,66.92c.29,3.67.84,8.18,3.09,11.21,1.42,1.92,3.72,2.79,5.87,3.64,3.67,1.46,5.51,3.67,7.86,6.79,3.82,5.1,7.45,9.28,13.41,11.94,4.95,2.21,9.84,2.76,14.94,2.91a.92.92,0,0,0,.38.08H57a.82.82,0,0,0,.28,0,42.11,42.11,0,0,0,11.52-1.56A30,30,0,0,0,84.13,90.75c2.52-3.37,4.82-6.16,8.55-8.21,2.43-1.33,5.5-2.52,7.26-4.76,2.25-2.87,2.75-7.31,2.93-10.86.19-4,.26-9.23-2.14-12.59A14.91,14.91,0,0,0,99.66,53c.55-3,1.11-5.9,1.66-8.85A4,4,0,0,0,105,40.19ZM101,62.13a40.3,40.3,0,0,1,.06,4.79c-.18,3.62-.71,8.31-3.64,10.72-2.57,2.11-5.9,3.11-8.56,5.08a26,26,0,0,0-5,5.36c-4.34,5.72-9,10.24-16.12,12.22a49.51,49.51,0,0,1-12.66,1.34c-6.48,0-12.63-.14-18.73-3S26.86,91.12,22.83,86a19.71,19.71,0,0,0-4-4.31c-2.07-1.43-4.72-1.76-6.84-3.13-3.51-2.25-4.12-7.75-4.42-11.6a38.29,38.29,0,0,1-.08-4.79c.17-3.76.82-5.91,3.51-8.51,4.44-4.29,3.94-11.43,4.56-17.12A29.29,29.29,0,0,1,28.2,14.73,47.62,47.62,0,0,1,54,6.49a.55.55,0,0,0,.18,0,.47.47,0,0,0,.17,0C71,6.77,89.61,16.4,92.43,34.15c1.09,6.88.09,13.67,5,19.26A13.06,13.06,0,0,1,101,62.13ZM98.51,40.19A2.53,2.53,0,1,1,101,42.71,2.53,2.53,0,0,1,98.51,40.19Z"
                }
            )
        ),

        edit: withInstanceId(function (props) {
            var curCamId   = props.attributes.id || '';
            var instanceId = props.instanceId;

            if (!campaigns) {
                // get the user's campaigns from the backend
                $.post(ajaxurl, {action: 'vessel_campaigns'}, function (resp) {
                    campaigns = JSON.parse(resp);

                    if (campaigns instanceof Array) {
                        options = campaigns.map(function (campaign) {
                            return el(
                                'option',
                                {
                                    value:    campaign.id,
                                    selected: curCamId.toString() === campaign.id.toString()
                                },
                                campaign.title
                            )
                        });



                        // if no id set, make it the first campaign in list
                        if (!curCamId && campaigns[0]) {
                            options.unshift(
                                el(
                                    'option',
                                    {
                                        value: '',
                                    },
                                    'Select one...'
                                )
                            );
                        }
                    }

                    // this will trigger the edit function after the options are populated.
                    props.setAttributes({trigger: Math.random()});
                });
            }


            function onChangeContent(e) {
                var camId = e.target.value;

                props.setAttributes({id: camId});
            }

            var elements = [
                el(
                    'img',
                    {
                        src: 'https://cdn.shopify.com/s/files/1/1790/9267/files/bnr_logo_blk_x58.png'
                    }
                ),
                el(
                    'h4',
                    null,
                    'Campaign Map'
                )
            ];

            // if options are set then display the form.
            if (options) {
                elements.push(
                    el(
                        'select',
                        {
                            id: 'vessel-campaign-select-' + instanceId,
                            onChange: onChangeContent
                        },
                        options
                    )
                );
            } else if (campaigns) {
                // campaigns will  be an error message at this point.
                elements.push(
                    el(
                        'p',
                        null,
                        [
                            el(
                                'span',
                                null,
                                campaigns.beforeLink
                            ),
                            el(
                                'a',
                                { href: campaigns.linkUrl },
                                campaigns.linkText
                            ),
                            el(
                                'span',
                                null,
                                campaigns.afterLink
                            )
                        ]
                    )
                );
            } else {
                elements.push(
                    el(
                        'p',
                        null,
                        "I'm loading..."
                    )
                );
            }

            return el(
                'div',
                {
                    style: blockStyle
                },
                elements
            );
        }),

        save: function (props) {
            // block will be rendered by backend using the shortcode renderer
            return null;
        }
    });
})(jQuery);
