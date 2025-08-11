import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { Fragment, useState, useEffect } from '@wordpress/element';
import { CheckboxControl, PanelBody, TextControl, TextareaControl, Modal, HorizontalRule, SelectControl } from '@wordpress/components';


const defaultFullcalendarConfig = JSON.stringify(window.pgc_trans.fullcalendar_version >= 5 ?
    {
        headerToolbar: {
            start: "prev,next today",
            center: "title",
            end: "dayGridMonth,timeGridWeek,listWeek"
        }
    }
    : {
        header: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,listWeek"
        }
    }, null, 2);

function getNewUpdatedObject(obj, objName, key, newValue) {
    const copy = Object.assign({}, obj);
    copy[key] = newValue;
    const newObj = {};
    newObj[objName] = copy;
    return newObj;
}

function hasValidFullCalendarConfigValueCheck(value) {
    try {
        return value === "" || Object.keys(JSON.parse(value)).length > 0;
    } catch (ex) {
        return false;
    }
}

const MyInfoModal = function (props) {
    return (
        <Modal
            title="FullCalendar config"
            onRequestClose={props.onClose}>
            <p dangerouslySetInnerHTML={{ __html: window.pgc_trans.copy_fullcalendar_config_info }} />
            <p dangerouslySetInnerHTML={{ __html: window.pgc_trans.fullcalendar_docs_link }} />

        </Modal>
    );
};

registerBlockType('pgc-plugin/calendar', {
    title: 'Private Google Calendars',
    icon: 'calendar',
    category: 'widgets',
    attributes: {
        calendars: {
            type: "object",
            default: {}
        },
        config: {
            type: "object",
            default: {
                filter: "top",
                theme: "",
                eventpopup: false,
                eventlink: false,
                eventdescription: false,
                eventlocation: false,
                eventattendees: false,
                eventattachments: false,
                eventcreator: false,
                eventcalendarname: false
            }
        },
        fullcalendarconfig: {
            type: "string",
            default: ""
        },
        publiccalendarids: {
            type: "string",
            default: ""
        },
        uncheckedcalendarids: {
            type: "string",
            default: ""
        },
        hideoptions: {
            type: "object",
            default: {
                hidefuture: false,
                hidefuturedays: 0,
                hidepassed: false,
                hidepasseddays: 0,
            }
        }
    },
    edit(props) {

        const [hasValidFullCalendarConfigValue, setHasValidFullCalendarConfigValue]
            = useState(hasValidFullCalendarConfigValueCheck(props.attributes.fullcalendarconfig));
        const [showConfigArea, setShowConfigArea] = useState(props.attributes.fullcalendarconfig);
        const [showInfoModal, setShowInfoModal] = useState(false);

        const calendars = props.attributes.calendars;
        let selectedCalendarCount = 0;
        Object.keys(calendars).forEach(function (key) {
            if (calendars[key]) selectedCalendarCount += 1;
        });
        const config = props.attributes.config;
        const hideoptions = props.attributes.hideoptions;
        const fullcalendarconfig = props.attributes.fullcalendarconfig;
        const publiccalendarids = props.attributes.publiccalendarids;
        const uncheckedcalendarids = props.attributes.uncheckedcalendarids;

        const onCalendarSelectionChange = function (newValue) {
            props.setAttributes(getNewUpdatedObject(calendars, "calendars", this, newValue));
        };

        const onCalendarConfigChange = function (newValue) {
            props.setAttributes(getNewUpdatedObject(config, "config", this, newValue));
        };

        const onHideoptionsChange = function (newValue) {
            props.setAttributes(getNewUpdatedObject(hideoptions, "hideoptions", this, newValue));
        };

        const onFullCalendarConfigChange = function (newValue) {
            setHasValidFullCalendarConfigValue(hasValidFullCalendarConfigValueCheck(newValue));
            props.setAttributes({ fullcalendarconfig: newValue === "" ? "" : newValue });
        };

        const onPublicCalendarIdsChange = function (newValue) {
            props.setAttributes({ publiccalendarids: newValue });
        };

        const onUncheckedCalendarIdsChange = function (newValue) {
            props.setAttributes({ uncheckedcalendarids: newValue });
        };

        const onAreaKeyDown = function (e) {
            if (e.keyCode == 9) {
                e.preventDefault();
                const area = e.target;
                const start = area.selectionStart;
                const content = area.value.substring(0, start) + "  " + area.value.substring(area.selectionEnd);
                onFullCalendarConfigChange(content);
                let t = setTimeout(() => {
                    clearTimeout(t);
                    area.selectionEnd = start + 2;
                }, 0);
            }
        };

        let calendarList = null;

        calendarList = Object.keys(window.pgc_selected_calendars).map((id) => {
            const calendar = window.pgc_selected_calendars[id];
            return <CheckboxControl style={{ backgroundColor: calendar.backgroundColor }} className="pgc-sidebar-row" onChange={onCalendarSelectionChange.bind(id)}
                label={calendar.summary} checked={(id in calendars) && calendars[id]} />
        });
        if (!calendarList.length) {
            calendarList.push(<em>No private calendars</em>);
        }
        calendarList.push(<HorizontalRule />);


        const eventPopupList = [
            ["eventpopup", window.pgc_trans.eventpopup],
            ["eventlink", window.pgc_trans.eventlink],
            ["eventdescription", window.pgc_trans.eventdescription],
            ["eventlocation", window.pgc_trans.eventlocation],
            ["eventattendees", window.pgc_trans.eventattendees],
            ["eventattachments", window.pgc_trans.eventattachments],
            ["eventcreator", window.pgc_trans.eventcreator],
            ["eventcalendarname", window.pgc_trans.eventcalendarname],
        ].map((item) => {
            return <CheckboxControl className="pgc-sidebar-row" onChange={onCalendarConfigChange.bind(item[0])}
                label={item[1]} checked={config[item[0]]} />;
        });

        const hidePassedDays = hideoptions.hidepassed
            ?
            <TextControl label={`${window.pgc_trans.more_than} ${hideoptions.hidepasseddays} ${window.pgc_trans.days_ago}`} type="number" min={0}
                value={hideoptions.hidepasseddays} onChange={onHideoptionsChange.bind('hidepasseddays')} />
            : null;
        const hideFutureDays = hideoptions.hidefuture
            ?
            <TextControl label={`${window.pgc_trans.more_than} ${hideoptions.hidefuturedays} ${window.pgc_trans.days_from_now}`} type="number" min={0}
                value={hideoptions.hidefuturedays} onChange={onHideoptionsChange.bind('hidefuturedays')} />
            : null;

        useEffect(() => {
            const unsubscribe = wp.data.subscribe(function () {
                if (wp.data.select("core/editor")) {
                    const isSavingPost = wp.data.select('core/editor').isSavingPost();
                    const isAutosavingPost = wp.data.select('core/editor').isAutosavingPost();
                    if (isSavingPost && !isAutosavingPost) {
                        if (!hasValidFullCalendarConfigValue) {
                            // Infinite loop when directly called, don't know why.
                            let t = setTimeout(function () {
                                clearTimeout(t);
                                wp.data.dispatch("core/notices").createWarningNotice(window.pgc_trans.malformed_json);
                            }, 0);
                            unsubscribe();
                        }
                        if (!selectedCalendarCount && !publiccalendarids) {
                            let t = setTimeout(function () {
                                clearTimeout(t);
                                wp.data.dispatch("core/notices").createWarningNotice(window.pgc_trans.enter_one_or_more_public_calendar_ids);
                            }, 0);
                            unsubscribe();
                        }
                    }
                }
            });
            return unsubscribe;
        });

        const fullCalendarConfigArea = showConfigArea ? (
            <Fragment>
                <TextareaControl rows={10} onKeyDown={onAreaKeyDown}
                    className={"pgc-fullcalendarconfigarea " + (hasValidFullCalendarConfigValue ? "" : "has-error")}
                    value={fullcalendarconfig}
                    help={!hasValidFullCalendarConfigValue ? window.pgc_trans.malformed_json_short : ""}
                    label={window.pgc_trans.fullcalendar_config}
                    placeHolder={defaultFullcalendarConfig} onChange={onFullCalendarConfigChange} />
                <div className="pgc-copy-link">
                    <a href="#" onClick={(e) => { e.preventDefault(); onFullCalendarConfigChange(defaultFullcalendarConfig) }}>{window.pgc_trans.copy_default_fullcalendar_config}</a>
                    <span onClick={() => setShowInfoModal(true)} class="dashicons dashicons-editor-help"></span>
                </div>
            </Fragment>
        ) : null;

        const infoModal = showInfoModal ? MyInfoModal({ onClose: () => { setShowInfoModal(false) } }) : null;

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody
                        title={window.pgc_trans.selected_calendars + " (" + (selectedCalendarCount === 0 ? window.pgc_trans.none : selectedCalendarCount) + ")"}
                        initialOpen={true}>
                        {calendarList}
                        <TextControl label={window.pgc_trans.comma_separated_list_calendar_ids} value={publiccalendarids} onChange={onPublicCalendarIdsChange} />
                    </PanelBody>
                    <PanelBody
                        title={window.pgc_trans.calendar_options}
                        initialOpen={true}>
                        <CheckboxControl className="pgc-sidebar-row" onChange={setShowConfigArea}
                            label={window.pgc_trans.edit_fullcalendar_config} checked={showConfigArea} />
                        <CheckboxControl className="pgc-sidebar-row" onChange={onHideoptionsChange.bind('hidepassed')}
                            label={window.pgc_trans.hide_passed_events} checked={hideoptions.hidepassed} />
                        {hidePassedDays}
                        <CheckboxControl className="pgc-sidebar-row" onChange={onHideoptionsChange.bind('hidefuture')}
                            label={window.pgc_trans.hide_future_events} checked={hideoptions.hidefuture} />
                        {hideFutureDays}
                    </PanelBody>
                    <PanelBody
                        title={window.pgc_trans.theme}
                        initialOpen={true}>
                        <SelectControl value={config.theme || ''} onChange={onCalendarConfigChange.bind('theme')} options={[
                            { value: '', label: window.pgc_trans.default }, ...window.pgc_trans.themes.map(function (theme) {
                                return { value: theme, label: theme };
                            })
                        ]} />
                    </PanelBody>
                    <PanelBody
                        title={window.pgc_trans.filter_options}>
                        <SelectControl value={config.filter === true ? "top" : config.filter} onChange={onCalendarConfigChange.bind('filter')} options={[
                            { value: '', label: window.pgc_trans.hide_filter },
                            { value: 'top', label: window.pgc_trans.show_filter_top },
                            { value: 'bottom', label: window.pgc_trans.show_filter_bottom }
                        ]} />
                        <TextControl label={window.pgc_trans.filter_uncheckedcalendarids} value={uncheckedcalendarids} onChange={onUncheckedCalendarIdsChange} />
                    </PanelBody>
                    <PanelBody
                        title={window.pgc_trans.popup_options + " (" + (config.eventpopup ? window.pgc_trans.show : window.pgc_trans.hide) + ")"}
                        initialOpen={true}>
                        {eventPopupList}
                    </PanelBody>
                </InspectorControls>
                <div>Private Google Calendars Block</div>
                {fullCalendarConfigArea}
                {infoModal}
            </Fragment>
        );
    },
    save(props) {
        const attrs = {};
        const attrsArray = [];
        const config = props.attributes.config;
        const hideoptions = props.attributes.hideoptions;
        const fullcalendarconfig = props.attributes.fullcalendarconfig;
        let hasValidConfig = false;
        try {
            hasValidConfig = fullcalendarconfig && Object.keys(JSON.parse(fullcalendarconfig)).length > 0;
        } catch (ex) {

        }
        if (hasValidConfig) {
            attrsArray.push(`fullcalendarconfig='${fullcalendarconfig}'`);
        }
        Object.keys(config).forEach(function (key) {
            if (key === 'filter' || key === 'theme') {
                attrsArray.push(key + '="' + (config[key]) + '"');
            } else {
                attrsArray.push(key + '="' + (config[key] ? 'true' : 'false') + '"');
            }
        });

        attrsArray.push(`hidepassed="${hideoptions.hidepassed ? hideoptions.hidepasseddays : 'false'}"`);
        attrsArray.push(`hidefuture="${hideoptions.hidefuture ? hideoptions.hidefuturedays : 'false'}"`);

        if (props.attributes.publiccalendarids || Object.keys(props.attributes.calendars).length) {
            attrs.calendarids = props.attributes.publiccalendarids;
            if (Object.keys(props.attributes.calendars).length) {
                const calendarids = [];
                Object.keys(props.attributes.calendars).forEach(function (id) {
                    if ((id in props.attributes.calendars) && props.attributes.calendars[id]) {
                        calendarids.push(id);
                    }
                });
                attrs.calendarids += (attrs.calendarids.length && calendarids.length ? "," : "") + calendarids.join(",");
            }
        }

        // Only if present set to save function.
        // This means we don't have to use a deprecated version for this, because in previous versions this was not present
        // and thus not displayed in save object.
        if (props.attributes.uncheckedcalendarids) {
            attrs.uncheckedcalendarids = props.attributes.uncheckedcalendarids;
        }

        Object.keys(attrs).forEach(function (key) {
            attrsArray.push(key + '="' + attrs[key] + '"');
        });

        return <p>[pgc {attrsArray.join(" ")}]</p>
    },
    deprecated: [
        {
            attributes: {
                calendars: {
                    type: "object",
                    default: {}
                },
                config: {
                    type: "object",
                    default: {
                        filter: "top",
                        eventpopup: false,
                        eventlink: false,
                        eventdescription: false,
                        eventlocation: false,
                        eventattendees: false,
                        eventattachments: false,
                        eventcreator: false,
                        eventcalendarname: false
                    }
                },
                fullcalendarconfig: {
                    type: "string",
                    default: ""
                },
                publiccalendarids: {
                    type: "string",
                    default: ""
                },
                uncheckedcalendarids: {
                    type: "string",
                    default: ""
                },
                hideoptions: {
                    type: "object",
                    default: {
                        hidefuture: false,
                        hidefuturedays: 0,
                        hidepassed: false,
                        hidepasseddays: 0,
                    }
                }
            },
            save(props) {
                const attrs = {};
                const attrsArray = [];
                const config = props.attributes.config;
                const hideoptions = props.attributes.hideoptions;
                const fullcalendarconfig = props.attributes.fullcalendarconfig;
                let hasValidConfig = false;
                try {
                    hasValidConfig = fullcalendarconfig && Object.keys(JSON.parse(fullcalendarconfig)).length > 0;
                } catch (ex) {

                }
                if (hasValidConfig) {
                    attrsArray.push(`fullcalendarconfig='${fullcalendarconfig}'`);
                }
                Object.keys(config).forEach(function (key) {
                    if (key === 'filter') {
                        attrsArray.push(key + '="' + (config[key]) + '"');
                    } else {
                        attrsArray.push(key + '="' + (config[key] ? 'true' : 'false') + '"');
                    }
                });

                attrsArray.push(`hidepassed="${hideoptions.hidepassed ? hideoptions.hidepasseddays : 'false'}"`);
                attrsArray.push(`hidefuture="${hideoptions.hidefuture ? hideoptions.hidefuturedays : 'false'}"`);

                if (props.attributes.publiccalendarids || Object.keys(props.attributes.calendars).length) {
                    attrs.calendarids = props.attributes.publiccalendarids;
                    if (Object.keys(props.attributes.calendars).length) {
                        const calendarids = [];
                        Object.keys(props.attributes.calendars).forEach(function (id) {
                            if ((id in props.attributes.calendars) && props.attributes.calendars[id]) {
                                calendarids.push(id);
                            }
                        });
                        attrs.calendarids += (attrs.calendarids.length && calendarids.length ? "," : "") + calendarids.join(",");
                    }
                }

                // Only if present set to save function.
                // This means we don't have to use a deprecated version for this, because in previous versions this was not present
                // and thus not displayed in save object.
                if (props.attributes.uncheckedcalendarids) {
                    attrs.uncheckedcalendarids = props.attributes.uncheckedcalendarids;
                }

                Object.keys(attrs).forEach(function (key) {
                    attrsArray.push(key + '="' + attrs[key] + '"');
                });

                return <p>[pgc {attrsArray.join(" ")}]</p>
            },
        },
        {
            attributes: {
                calendars: {
                    type: "object",
                    default: {}
                },
                config: {
                    type: "object",
                    default: {
                        public: false,
                        filter: "top",
                        eventpopup: false,
                        eventlink: false,
                        eventdescription: false,
                        eventlocation: false,
                        eventattendees: false,
                        eventattachments: false,
                        eventcreator: false,
                        eventcalendarname: false
                    }
                },
                fullcalendarconfig: {
                    type: "string",
                    default: ""
                },
                publiccalendarids: {
                    type: "string",
                    default: ""
                },
                uncheckedcalendarids: {
                    type: "string",
                    default: ""
                },
                hideoptions: {
                    type: "object",
                    default: {
                        hidefuture: false,
                        hidefuturedays: 0,
                        hidepassed: false,
                        hidepasseddays: 0,
                    }
                }
            },
            save(props) {
                const attrs = {};
                const attrsArray = [];
                const config = props.attributes.config;
                const hideoptions = props.attributes.hideoptions;
                const fullcalendarconfig = props.attributes.fullcalendarconfig;
                let hasValidConfig = false;
                try {
                    hasValidConfig = fullcalendarconfig && Object.keys(JSON.parse(fullcalendarconfig)).length > 0;
                } catch (ex) {

                }
                if (hasValidConfig) {
                    attrsArray.push(`fullcalendarconfig='${fullcalendarconfig}'`);
                }
                Object.keys(config).forEach(function (key) {
                    if (key === 'filter') {
                        attrsArray.push(key + '="' + (config[key]) + '"');
                    } else {
                        attrsArray.push(key + '="' + (config[key] ? 'true' : 'false') + '"');
                    }
                });

                attrsArray.push(`hidepassed="${hideoptions.hidepassed ? hideoptions.hidepasseddays : 'false'}"`);
                attrsArray.push(`hidefuture="${hideoptions.hidefuture ? hideoptions.hidefuturedays : 'false'}"`);

                if (props.attributes.config.public) {
                    attrs.calendarids = props.attributes.publiccalendarids;
                } else {
                    if (Object.keys(props.attributes.calendars).length) {
                        const calendarids = [];
                        Object.keys(props.attributes.calendars).forEach(function (id) {
                            if ((id in props.attributes.calendars) && props.attributes.calendars[id]) {
                                calendarids.push(id);
                            }
                        });
                        if (calendarids.length) {
                            attrs.calendarids = calendarids.join(",");
                        }
                    }
                }

                // Only if present set to save function.
                // This means we don't have to use a deprecated version for this, because in previous versions this was not present
                // and thus not displayed in save object.
                if (props.attributes.uncheckedcalendarids) {
                    attrs.uncheckedcalendarids = props.attributes.uncheckedcalendarids;
                }

                Object.keys(attrs).forEach(function (key) {
                    attrsArray.push(key + '="' + attrs[key] + '"');
                });

                return <p>[pgc {attrsArray.join(" ")}]</p>
            }
        },
        {
            attributes: {
                calendars: {
                    type: "object",
                    default: {}
                },
                config: {
                    type: "object",
                    default: {
                        public: false,
                        filter: true,
                        eventpopup: false,
                        eventlink: false,
                        eventdescription: false,
                        eventlocation: false,
                        eventattendees: false,
                        eventattachments: false,
                        eventcreator: false,
                        eventcalendarname: false
                    }
                },
                fullcalendarconfig: {
                    type: "string",
                    default: ""
                },
                publiccalendarids: {
                    type: "string",
                    default: ""
                },
                hideoptions: {
                    type: "object",
                    default: {
                        hidefuture: false,
                        hidefuturedays: 0,
                        hidepassed: false,
                        hidepasseddays: 0,
                    }
                }
            },
            save: function (props) {
                const attrs = {};
                const attrsArray = [];
                const config = props.attributes.config;
                const hideoptions = props.attributes.hideoptions;
                const fullcalendarconfig = props.attributes.fullcalendarconfig;
                let hasValidConfig = false;
                try {
                    hasValidConfig = fullcalendarconfig && Object.keys(JSON.parse(fullcalendarconfig)).length > 0;
                } catch (ex) {

                }
                if (hasValidConfig) {
                    attrsArray.push(`fullcalendarconfig='${fullcalendarconfig}'`);
                }
                Object.keys(config).forEach(function (key) {
                    attrsArray.push(key + '="' + (config[key] ? 'true' : 'false') + '"');
                });

                attrsArray.push(`hidepassed="${hideoptions.hidepassed ? hideoptions.hidepasseddays : 'false'}"`);
                attrsArray.push(`hidefuture="${hideoptions.hidefuture ? hideoptions.hidefuturedays : 'false'}"`);

                if (props.attributes.config.public) {
                    attrs.calendarids = props.attributes.publiccalendarids;
                } else {
                    if (Object.keys(props.attributes.calendars).length) {
                        const calendarids = [];
                        Object.keys(props.attributes.calendars).forEach(function (id) {
                            if ((id in props.attributes.calendars) && props.attributes.calendars[id]) {
                                calendarids.push(id);
                            }
                        });
                        if (calendarids.length) {
                            attrs.calendarids = calendarids.join(",");
                        }

                    }
                }

                Object.keys(attrs).forEach(function (key) {
                    attrsArray.push(key + '="' + attrs[key] + '"');
                });

                return <p>[pgc {attrsArray.join(" ")}]</p>
            }
        }
    ]
});