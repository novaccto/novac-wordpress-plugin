import { useState, useEffect } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { Button, TextControl, SelectControl, Card, CardBody, Spinner, Notice, TabPanel } from "@wordpress/components";
import "./style.css";

const SettingsTab = () => {
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
        <Card>
            <CardBody>
                <TextControl
                    label="Public Key"
                    value={settings.public_key}
                    onChange={(v) => setSettings({ ...settings, public_key: v })}
                    help="Your Novac public API key"
                />
                <TextControl
                    label="Secret Key"
                    type="password"
                    value={settings.secret_key}
                    onChange={(v) => setSettings({ ...settings, secret_key: v })}
                    help="Your Novac secret API key"
                />
                <SelectControl
                    label="Mode"
                    value={settings.mode}
                    options={[
                        { label: "Test", value: "test" },
                        { label: "Live", value: "live" },
                    ]}
                    onChange={(v) => setSettings({ ...settings, mode: v })}
                    help="Select test mode for development or live mode for production"
                />
                <TextControl
                    label="Webhook URL"
                    value={settings.webhook_url}
                    disabled
                    help="Configure this URL in your Novac dashboard"
                />
                <Button variant="primary" onClick={saveSettings} disabled={loading}>
                    {loading ? "Saving..." : "Save Settings"}
                </Button>

                {notice && <Notice status={notice.includes("✅") ? "success" : "error"} isDismissible onRemove={() => setNotice("")}>{notice}</Notice>}
            </CardBody>
        </Card>
    );
};

const TransactionsTab = () => {
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [search, setSearch] = useState("");
    const [statusFilter, setStatusFilter] = useState("");

    const fetchTransactions = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ page, per_page: 20 });
            if (search) params.append("search", search);
            if (statusFilter) params.append("status", statusFilter);

            const res = await apiFetch({ path: `novac/v1/transactions?${params}` });
            setTransactions(res.items || []);
            setTotal(res.total || 0);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTransactions();
    }, [page, statusFilter]);

    const handleSearch = () => {
        setPage(1);
        fetchTransactions();
    };

    const formatAmount = (amount, currency) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency || 'NGN'
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        const statusColors = {
            success: '#28a745',
            successful: '#28a745',
            pending: '#ffc107',
            failed: '#dc3545',
        };
        const color = statusColors[status] || '#6c757d';
        return (
            <span style={{
                padding: '4px 12px',
                borderRadius: '12px',
                backgroundColor: color,
                color: 'white',
                fontSize: '12px',
                fontWeight: '600',
                textTransform: 'uppercase'
            }}>
                {status}
            </span>
        );
    };

    if (loading && transactions.length === 0) return <Spinner />;

    const perPage = 20;
    const totalPages = Math.ceil(total / perPage);

    return (
        <Card>
            <CardBody>
                <div className="novac-transactions-header">
                    <div className="novac-search-bar">
                        <TextControl
                            placeholder="Search by email, name, or reference..."
                            value={search}
                            onChange={setSearch}
                        />
                        <Button variant="secondary" onClick={handleSearch}>Search</Button>
                    </div>
                    <SelectControl
                        label="Status"
                        value={statusFilter}
                        options={[
                            { label: "All Statuses", value: "" },
                            { label: "Successful", value: "successful" },
                            { label: "Pending", value: "pending" },
                            { label: "Failed", value: "failed" },
                        ]}
                        onChange={setStatusFilter}
                    />
                </div>

                {loading && <Spinner />}

                {!loading && transactions.length === 0 && (
                    <Notice status="info" isDismissible={false}>
                        No transactions found.
                    </Notice>
                )}

                {!loading && transactions.length > 0 && (
                    <div className="novac-transactions-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {transactions.map((tx) => (
                                    <tr key={tx.id}>
                                        <td><code>{tx.transaction_ref}</code></td>
                                        <td>
                                            <div><strong>{tx.customer_name || 'N/A'}</strong></div>
                                            <div style={{ fontSize: '12px', color: '#666' }}>{tx.customer_email}</div>
                                        </td>
                                        <td><strong>{formatAmount(tx.amount, tx.currency)}</strong></td>
                                        <td>{getStatusBadge(tx.status)}</td>
                                        <td>{new Date(tx.created_at).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {totalPages > 1 && (
                            <div className="novac-pagination">
                                <Button
                                    variant="secondary"
                                    disabled={page <= 1}
                                    onClick={() => setPage(page - 1)}
                                >
                                    Previous
                                </Button>
                                <span>Page {page} of {totalPages} ({total} total)</span>
                                <Button
                                    variant="secondary"
                                    disabled={page >= totalPages}
                                    onClick={() => setPage(page + 1)}
                                >
                                    Next
                                </Button>
                            </div>
                        )}
                    </div>
                )}
            </CardBody>
        </Card>
    );
};

const App = () => {
    const currentPage = window.novacData?.page || 'novac-settings';

    return (
        <div className="novac-admin">
            <div className="novac-header">
                <h1>💳 Novac Payments</h1>
                <p className="novac-subtitle">Manage your payment gateway settings and transactions</p>
            </div>

            <nav className="nav-tab-wrapper">
                <a 
                    href="?page=novac-settings" 
                    className={`nav-tab ${currentPage === 'novac-settings' ? 'nav-tab-active' : ''}`}
                >
                    Settings
                </a>
                <a 
                    href="?page=novac-transactions" 
                    className={`nav-tab ${currentPage === 'novac-transactions' ? 'nav-tab-active' : ''}`}
                >
                    Transactions
                </a>
            </nav>

            <div className="novac-content">
                {currentPage === 'novac-settings' && <SettingsTab />}
                {currentPage === 'novac-transactions' && <TransactionsTab />}
            </div>
        </div>
    );
};

wp.element.render(<App />, document.getElementById("novac-admin-root"));

