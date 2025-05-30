import { useState, useEffect } from "react";
import {
    Page,
    Layout,
    Card,
    Button,
    Text,
    BlockStack,
    InlineStack,
    Box,
    List,
    Divider,
    Banner,
} from "@shopify/polaris";

import { useNavigate } from "react-router-dom";

export default function PlansPage() {
    const navigate = useNavigate();
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [hasSubscription, setHasSubscription] = useState(false);

    useEffect(() => {
        const fetchPlans = async () => {
            try {
                const plansResponse = await fetch("/api/plans");
                const plansData = await plansResponse.json();
                setPlans(plansData.plans);

                const subscriptionResponse = await fetch("/api/plans/check");
                const subscriptionData = await subscriptionResponse.json();
                setHasSubscription(subscriptionData.hasActiveSubscription);
            } catch (error) {
                console.error("Error fetching plans:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchPlans();
    }, [fetch]);

    const handleSubscribe = async (planId) => {
        try {
            setLoading(true);
            const response = await fetch("/api/plans/subscribe", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ planId }),
            });

            const data = await response.json();

            if (data.hasActiveSubscription) {
                setHasSubscription(true);
                navigate("/");
            } else if (data.confirmationUrl) {
                window.open(data.confirmationUrl, "_top");
            }
        } catch (error) {
            console.error("Error subscribing to plan:", error);
        } finally {
            setLoading(false);
        }
    };
    const SpacingBackground = ({ children }) => {
        return (
            <div
                style={{
                    background: "var(--p-color-bg-surface-success)",
                    height: "auto",
                }}
            >
                {children}
            </div>
        );
    };
    return (
        <Page title="Subscription Plans">
            <Layout>
                {hasSubscription && (
                    <Layout.Section>
                        <Banner title="Active Subscription" tone="success">
                            <p>
                                You already have an active subscription. Enjoy
                                all the premium features!
                            </p>
                        </Banner>
                    </Layout.Section>
                )}

                <Layout.Section>
                    <BlockStack gap="500">
                        <InlineStack gap="500" align="center">
                            {plans.map((plan) => (
                                <Box key={plan.id} width="50%">
                                    <Card>
                                        <BlockStack gap="400">
                                            <Text variant="headingLg" as="h2">
                                                {plan.name}
                                            </Text>
                                            <Text variant="headingXl" as="p">
                                                ${plan.price}/month
                                            </Text>
                                            <Divider />
                                            <Text variant="bodyMd" as="p">
                                                Features:
                                            </Text>
                                            <List type="bullet">
                                                {plan.features.map(
                                                    (feature, index) => (
                                                        <List.Item key={index}>
                                                            {feature}
                                                        </List.Item>
                                                    ),
                                                )}
                                            </List>
                                            <Button
                                                primary
                                                disabled={
                                                    loading || hasSubscription
                                                }
                                                onClick={() =>
                                                    handleSubscribe(plan.id)
                                                }
                                            >
                                                {hasSubscription
                                                    ? "Current Plan"
                                                    : "Subscribe"}
                                            </Button>
                                        </BlockStack>
                                    </Card>
                                </Box>
                            ))}
                        </InlineStack>
                    </BlockStack>
                </Layout.Section>
            </Layout>
        </Page>
    );
}
