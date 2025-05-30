import { Card, Page, Layout, Text } from "@shopify/polaris";
import { TitleBar } from "@shopify/app-bridge-react";
import { useTranslation, Trans } from "react-i18next";

import { ProductsCard } from "../components";

export default function HomePage() {
    const { t } = useTranslation();
    return (
        <Page narrowWidth>
            <TitleBar title={t("HomePage.title")} />
            <Layout>
                <Layout.Section>
                    <Card sectioned>
                        <Text as="h2" variant="headingMd">
                            Home
                        </Text>
                    </Card>
                </Layout.Section>
                <Layout.Section>
                    <ProductsCard />
                </Layout.Section>
            </Layout>
        </Page>
    );
}
