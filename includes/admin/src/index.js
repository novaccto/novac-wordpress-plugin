import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { Button, TextControl, SelectControl, Card, CardBody, Spinner, Notice } from "@wordpress/components";

const SettingsPage = () => {
    const [settings, setSettings] = useState(null);
    const [loading, setLoading] = useState(true);
    const [notice, setNotice] = useState("");

    useEffect(() => {
        apiFetch({ path: "novac/v1/settings" }).then(setSettings).finally(() => setLoading(false));
    }, []);

    const saveSettings = async () => {
        setLoading(true);
        setNotice("");
        try {
            const res = await apiFetch({
                path: "novac/v1/settings",
                method: "POST",
                data: settings,
            });
            setSettings(res);
            setNotice("✅ Settings saved successfully.");
        } catch (e) {
            setNotice("❌ Failed to save settings.");
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <Spinner />;

    return (
        <div className="novac-admin">
            <h1>Novac Payment Settings</h1>
            <Card>
                <CardBody>
                    <TextControl
                        label="Public Key"
                        value={settings.public_key}
                        onChange={(v) => setSettings({ ...settings, public_key: v })}
                    />
                    <TextControl
                        label="Secret Key"
                        type="password"
                        value={settings.secret_key}
                        onChange={(v) => setSettings({ ...settings, secret_key: v })}
                    />
                    <SelectControl
                        label="Mode"
                        value={settings.mode}
                        options={[
                            { label: "Test", value: "test" },
                            { label: "Live", value: "live" },
                        ]}
                        onChange={(v) => setSettings({ ...settings, mode: v })}
                    />
                    <TextControl
                        label="Webhook URL"
                        value={settings.webhook_url}
                        disabled
                    />
                    <Button variant="primary" onClick={saveSettings} disabled={loading}>
                        {loading ? "Saving..." : "Save Settings"}
                    </Button>

                    {notice && <Notice status="info" isDismissible>{notice}</Notice>}
                </CardBody>
            </Card>
        </div>
    );
};

wp.element.render(<SettingsPage />, document.getElementById("novac-admin-root"));
