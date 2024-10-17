import {sprintf, __} from '@wordpress/i18n';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting, WC_ASSET_URL} from '@woocommerce/settings';

const {registerPaymentMethod} = window.wc.wcBlocksRegistry;

const settings = getSetting('hashgate_data', {});

console.log(settings)

const defaultLabel = __(
    'HashGate',
    'hashgate'
);

const label = decodeEntities(settings.title) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
    return decodeEntities(settings.description || '');
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
    const {PaymentMethodLabel} = props.components;
    return <div style={ {display: 'flex', "align-items": 'center'}}>
        <img
            src={`/wp-content/plugins/hashgate/assets/images/logo.png`}
            alt={label}
            style={ { 'margin-right': '8px'}}
        />
        <PaymentMethodLabel text={label}/>
    </div>;
};

/**
 * Dummy payment method config object.
 */
const Dummy = {
    name: "hashgate",
    label: <Label/>,
    placeOrderButtonLabel: __(
        'Proceed to HashGate',
        'woo-gutenberg-products-block'
    ),
    content: <Content/>,
    edit: <Content/>,
    canMakePayment: () => true,
    ariaLabel: decodeEntities(
        settings.title ||
        __('Payment via HashGate', 'woo-gutenberg-products-block')
    ),
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Dummy);
