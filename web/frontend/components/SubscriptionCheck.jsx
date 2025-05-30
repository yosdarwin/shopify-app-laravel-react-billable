import { useEffect, useState } from "react";
import { Banner, Button } from "@shopify/polaris";
import { useNavigate } from "react-router-dom";

export function SubscriptionCheck({ children }) {
    const navigate = useNavigate();
    const [hasSubscription, setHasSubscription] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const checkSubscription = async () => {
            try {
                const response = await fetch("/api/plans/check");
                const data = await response.json();
                setHasSubscription(data.hasActiveSubscription);
            } catch (error) {
                console.error("Error checking subscription:", error);
            } finally {
                setLoading(false);
            }
        };

        checkSubscription();
    }, [fetch]);

    if (loading) {
        return null;
    }

    if (!hasSubscription) {
        return (
            <Banner
                title="Subscription Required"
                tone="warning"
                action={{
                    content: "View Plans",
                    onAction: () => navigate("/plans"),
                }}
            >
                <p>
                    You need an active subscription to access all features.
                    Please subscribe to a plan.
                </p>
            </Banner>
        );
    }

    return children;
}
