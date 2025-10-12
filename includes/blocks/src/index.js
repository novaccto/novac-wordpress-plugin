const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, SelectControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('novac/payment-form', {
    title: __('Novac Payment Form', 'novac'),
    description: __('Accept payments using Novac Payment Gateway', 'novac'),
    icon: 'money-alt',
    category: 'common',
    attributes: {
        amount: {
            type: 'string',
            default: '',
        },
        currency: {
            type: 'string',
            default: 'NGN',
        },
        description: {
            type: 'string',
            default: 'Payment',
        },
        buttonText: {
            type: 'string',
            default: 'Pay Now',
        },
    },

    edit: ({ attributes, setAttributes }) => {
        const { amount, currency, description, buttonText } = attributes;

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Payment Settings', 'novac')}>
                        <TextControl
                            label={__('Fixed Amount', 'novac')}
                            help={__('Leave empty to let users enter amount', 'novac')}
                            value={amount}
                            onChange={(value) => setAttributes({ amount: value })}
                            type="number"
                        />
                        <SelectControl
                            label={__('Currency', 'novac')}
                            value={currency}
                            options={[
                                { label: 'NGN - Nigerian Naira', value: 'NGN' },
                                { label: 'USD - US Dollar', value: 'USD' },
                                { label: 'EUR - Euro', value: 'EUR' },
                                { label: 'GBP - British Pound', value: 'GBP' },
                            ]}
                            onChange={(value) => setAttributes({ currency: value })}
                        />
                        <TextControl
                            label={__('Description', 'novac')}
                            value={description}
                            onChange={(value) => setAttributes({ description: value })}
                        />
                        <TextControl
                            label={__('Button Text', 'novac')}
                            value={buttonText}
                            onChange={(value) => setAttributes({ buttonText: value })}
                        />
                    </PanelBody>
                </InspectorControls>

                <div className="novac-block-preview" style={{
                    padding: '20px',
                    background: '#f8f9fa',
                    border: '1px dashed #ddd',
                    borderRadius: '4px',
                    textAlign: 'center'
                }}>
                    <div style={{ fontSize: '24px', marginBottom: '10px' }}>💳</div>
                    <h3 style={{ margin: '10px 0' }}>{__('Novac Payment Form', 'novac')}</h3>
                    <p style={{ color: '#666', fontSize: '14px', margin: '10px 0' }}>
                        {amount ? (
                            <>Amount: <strong>{amount} {currency}</strong></>
                        ) : (
                            __('Customer enters amount', 'novac')
                        )}
                    </p>
                    <p style={{ color: '#666', fontSize: '14px', margin: '10px 0' }}>
                        {description}
                    </p>
                    <button style={{
                        padding: '10px 20px',
                        background: '#3498db',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '14px',
                        fontWeight: '600'
                    }}>
                        {buttonText}
                    </button>
                    <p style={{ marginTop: '15px', fontSize: '12px', color: '#999' }}>
                        {__('This is a preview. The actual form will be displayed on the frontend.', 'novac')}
                    </p>
                </div>
            </>
        );
    },

    save: () => {
        // Rendered via PHP callback
        return null;
    },
});
